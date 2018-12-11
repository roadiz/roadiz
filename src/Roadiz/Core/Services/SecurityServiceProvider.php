<?php
/**
 * Copyright © 2014, Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file SecurityServiceProvider.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Services;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\Roadiz\Core\Authorization\AccessDeniedHandler;
use RZ\Roadiz\Core\Entities\Role;
use RZ\Roadiz\Core\Entities\User;
use RZ\Roadiz\Core\Exceptions\NoConfigurationFoundException;
use RZ\Roadiz\Core\Handlers\UserProvider;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Utils\Security\DoctrineRoleHierarchy;
use RZ\Roadiz\Utils\Security\TimedFirewall;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;
use Symfony\Component\Security\Core\Authentication\AuthenticationProviderManager;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolver;
use Symfony\Component\Security\Core\Authentication\Provider\AnonymousAuthenticationProvider;
use Symfony\Component\Security\Core\Authentication\Provider\DaoAuthenticationProvider;
use Symfony\Component\Security\Core\Authentication\Provider\RememberMeAuthenticationProvider;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\RememberMeToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManager;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Core\Authorization\Voter\RoleHierarchyVoter;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;
use Symfony\Component\Security\Core\User\UserChecker;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Security\Csrf\TokenGenerator\UriSafeTokenGenerator;
use Symfony\Component\Security\Csrf\TokenStorage\SessionTokenStorage;
use Symfony\Component\Security\Http\AccessMap;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Firewall\AccessListener;
use Symfony\Component\Security\Http\Firewall\ContextListener;
use Symfony\Component\Security\Http\Firewall\RememberMeListener;
use Symfony\Component\Security\Http\Firewall\SwitchUserListener;
use Symfony\Component\Security\Http\FirewallMap;
use Symfony\Component\Security\Http\Logout\CookieClearingLogoutHandler;
use Symfony\Component\Security\Http\RememberMe\TokenBasedRememberMeServices;

/**
 * Register security services for dependency injection container.
 */
class SecurityServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Container $container
     * @return Container
     */
    public function register(Container $container)
    {
        /*
         * PDO instance only used with SessionStorage
         */
        $container['session.pdo'] = function ($c) {
            if (isset($c['config']["sessionStorage"])) {
                $pdo = new \PDO(
                    $c['config']["sessionStorage"]['dsn'],
                    $c['config']["sessionStorage"]['user'],
                    $c['config']["sessionStorage"]['password']
                );
                $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

                return $pdo;
            }
            return null;
        };

        $container['session.storage'] = function ($c) {
            try {
                if ($c['config'] !== null &&
                    isset($c['config']["sessionStorage"])) {
                    if ($c['config']["sessionStorage"]["type"] === "pdo" &&
                        isset($c['config']["sessionStorage"]["options"])) {
                        return new NativeSessionStorage(
                            [],
                            new PdoSessionHandler(
                                $c['session.pdo'],
                                $c['config']["sessionStorage"]["options"]
                            )
                        );
                    }
                }
            } catch (NoConfigurationFoundException $e) {
                return null;
            }

            return null;
        };

        $container['session'] = function ($c) {
            /** @var RequestStack $requestStack */
            $requestStack = $c['requestStack'];
            $request = $requestStack->getCurrentRequest();
            $session = new Session($c['session.storage']);
            if (null !== $request) {
                $request->setSession($session);
            }
            return $session;
        };

        $container['sessionTokenStorage'] = function ($c) {
            return new SessionTokenStorage(
                $c['session'],
                $c['config']["security"]['secret']
            );
        };

        $container['csrfTokenManager'] = function ($c) {
            return new CsrfTokenManager(
                new UriSafeTokenGenerator(),
                $c['sessionTokenStorage']
            );
        };

        $container['securityAuthenticationUtils'] = function ($c) {
            return new AuthenticationUtils($c['requestStack']);
        };



        $container['contextListener'] = function ($c) {
            $c['session']; //Force session handler
            return new ContextListener(
                $c['securityTokenStorage'],
                [
                    $c['userProvider'],
                ],
                Kernel::SECURITY_DOMAIN,
                $c['logger'],
                $c['dispatcher']
            );
        };

        $container['accessMap'] = function () {
            return new AccessMap();
        };

        $container['userProvider'] = function ($c) {
            return new UserProvider($c['em']);
        };
        $container['userChecker'] = function () {
            return new UserChecker();
        };

        $container['daoAuthenticationProvider'] = function ($c) {
            return new DaoAuthenticationProvider(
                $c['userProvider'],
                $c['userChecker'],
                Kernel::SECURITY_DOMAIN,
                $c['userEncoderFactory']
            );
        };

        $container['rememberMeAuthenticationProvider'] = function ($c) {
            return new RememberMeAuthenticationProvider(
                $c['userChecker'],
                $c['config']["security"]['secret'],
                Kernel::SECURITY_DOMAIN
            );
        };

        $container['rememberMeCookieName'] = 'roadiz_remember_me';
        $container['rememberMeCookieLifetime'] = function ($c) {
            if (isset($c['config']['rememberMeLifetime'])) {
                return (int) $c['config']['rememberMeLifetime'];
            } else {
                // One month long cookie
                return 60 * 60 * 24 * 30;
            }
        };

        $container['cookieClearingLogoutHandler'] = function ($c) {
            /** @var RequestStack $requestStack */
            $requestStack = $c['requestStack'];
            $request = $requestStack->getMasterRequest();
            return new CookieClearingLogoutHandler([
                $c['rememberMeCookieName'] => [
                    'path' => $request->getBasePath(),
                    'domain' => $request->getHost(),
                ],
            ]);
        };

        $container['tokenBasedRememberMeServices'] = function ($c) {
            /** @var RequestStack $requestStack */
            $requestStack = $c['requestStack'];
            $request = $requestStack->getMasterRequest();
            return new TokenBasedRememberMeServices(
                [$c['userProvider']],
                $c['config']["security"]['secret'],
                Kernel::SECURITY_DOMAIN,
                [
                    'name' => $c['rememberMeCookieName'],
                    'lifetime' => $c['rememberMeCookieLifetime'],
                    'remember_me_parameter' => '_remember_me',
                    'path' => $request->getBasePath(),
                    'domain' => $request->getHost(),
                    'always_remember_me' => false,
                    'secure' => false,
                    'httponly' => false,
                ],
                $c['kernel']->isDebug() ? $c['logger'] : null
            );
        };

        $container['rememberMeListener'] = function ($c) {
            return new RememberMeListener(
                $c['securityTokenStorage'],
                $c['tokenBasedRememberMeServices'],
                $c['authentificationManager'],
                $c['kernel']->isDebug() ? $c['logger'] : null,
                $c['dispatcher']
            );
        };

        $container['authentificationManager'] = function ($c) {
            return new AuthenticationProviderManager([
                new AnonymousAuthenticationProvider($c['config']["security"]['secret']),
                $c['rememberMeAuthenticationProvider'],
                $c['daoAuthenticationProvider'],
            ]);
        };

        /*
         * Main decision manager, set your voters here.
         */
        $container['accessDecisionManager'] = function ($c) {
            return new AccessDecisionManager([
                new AuthenticatedVoter($c['securityAuthentificationTrustResolver']),
                $c['roleHierarchyVoter'],
            ]);
        };

        $container['securityAuthentificationTrustResolver'] = function ($c) {
            return new AuthenticationTrustResolver(
                AnonymousToken::class,
                RememberMeToken::class
            );
        };

        $container['securityAuthorizationChecker'] = function ($c) {
            return new AuthorizationChecker(
                $c['securityTokenStorage'],
                $c['authentificationManager'],
                $c['accessDecisionManager']
            );
        };

        $container['securityTokenStorage'] = function () {
            return new TokenStorage();
        };

        $container['securityAccessListener'] = function ($c) {
            return new AccessListener(
                $c['securityTokenStorage'],
                $c['accessDecisionManager'],
                $c['accessMap'],
                $c['authentificationManager']
            );
        };

        $container['roleHierarchy'] = function ($c) {
            /** @var Kernel $kernel */
            $kernel = $c['kernel'];
            if ($kernel->isInstallMode()) {
                return new DoctrineRoleHierarchy(null);
            }
            return new DoctrineRoleHierarchy($c['em']);
        };

        $container['roleHierarchyVoter'] = function ($c) {
            return new RoleHierarchyVoter($c['roleHierarchy']);
        };

        $container["switchUser"] = function ($c) {
            return new SwitchUserListener(
                $c['securityTokenStorage'],
                $c['userProvider'],
                $c['userChecker'],
                $c['config']["security"]['secret'],
                $c['accessDecisionManager'],
                $c['logger'],
                '_su',
                Role::ROLE_SUPERADMIN,
                $c['dispatcher']
            );
        };

        $container['firewallMap'] = function () {
            return new FirewallMap();
        };

        $container['passwordEncoder'] = function () {
            return new MessageDigestPasswordEncoder('sha512', true, 5000);
        };

        $container['userImplementations'] = function ($c) {
            return [
                User::class => $c['passwordEncoder'],
            ];
        };

        $container['userEncoderFactory'] = function ($c) {
            return new EncoderFactory($c['userImplementations']);
        };

        $container['firewall'] = function ($c) {
            $c['stopwatch']->start('firewall');
            $firewall = new TimedFirewall($c['firewallMap'], $c['dispatcher'], $c['stopwatch']);
            $c['stopwatch']->stop('firewall');

            return $firewall;
        };

        /*
         * Default denied handler
         */
        $container['accessDeniedHandler'] = function ($c) {
            return new AccessDeniedHandler($c['urlGenerator'], $c['logger']);
        };

        return $container;
    }
}
