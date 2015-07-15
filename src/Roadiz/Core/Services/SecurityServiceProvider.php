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

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Pimple\Container;
use RZ\Roadiz\Core\Authorization\AccessDeniedHandler;
use RZ\Roadiz\Core\Entities\Role;
use RZ\Roadiz\Core\Handlers\UserProvider;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\Log\DoctrineHandler;
use RZ\Roadiz\Utils\LogProcessors\RequestProcessor;
use RZ\Roadiz\Utils\LogProcessors\TokenStorageProcessor;
use Symfony\Component\Form\Extension\Csrf\CsrfProvider\SessionCsrfProvider;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;
use Symfony\Component\Security\Core\Authentication\AuthenticationProviderManager;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolver;
use Symfony\Component\Security\Core\Authentication\Provider\DaoAuthenticationProvider;
use Symfony\Component\Security\Core\Authentication\Provider\RememberMeAuthenticationProvider;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManager;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\Authorization\Voter\RoleHierarchyVoter;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;
use Symfony\Component\Security\Core\Role\RoleHierarchy;
use Symfony\Component\Security\Core\User\UserChecker;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Security\Csrf\TokenGenerator\UriSafeTokenGenerator;
use Symfony\Component\Security\Csrf\TokenStorage\SessionTokenStorage;
use Symfony\Component\Security\Http\AccessMap;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\EntryPoint\FormAuthenticationEntryPoint;
use Symfony\Component\Security\Http\Firewall;
use Symfony\Component\Security\Http\FirewallMap;
use Symfony\Component\Security\Http\Firewall\ContextListener;
use Symfony\Component\Security\Http\Firewall\ExceptionListener;
use Symfony\Component\Security\Http\Firewall\RememberMeListener;
use Symfony\Component\Security\Http\Firewall\SwitchUserListener;
use Symfony\Component\Security\Http\RememberMe\TokenBasedRememberMeServices;

/**
 * Register security services for dependency injection container.
 */
class SecurityServiceProvider implements \Pimple\ServiceProviderInterface
{
    public function register(Container $container)
    {
        /*
         * PDO instance only used with SessionStorage
         */
        $container['session.pdo'] = function ($c) {
            $pdo = new \PDO(
                $c['config']["sessionStorage"]['dsn'],
                $c['config']["sessionStorage"]['user'],
                $c['config']["sessionStorage"]['password']
            );
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            return $pdo;
        };

        $container['session.storage'] = function ($c) {
            if ($c['config'] !== null &&
                isset($c['config']["sessionStorage"])) {
                if ($c['config']["sessionStorage"]["type"] == "pdo" &&
                    isset($c['config']["sessionStorage"]["options"])) {
                    return new NativeSessionStorage(
                        [],
                        new PdoSessionHandler(
                            $c['session.pdo'],
                            $c['config']["sessionStorage"]["options"]
                        )
                    );
                }
            } else {
                return null;
            }
        };

        $container['session'] = function ($c) {
            $session = new Session($c['session.storage']);
            $c['request']->setSession($session);
            return $session;
        };

        /*
         * Deprecated
         */
        $container['csrfProvider'] = function ($c) {
            return new SessionCsrfProvider(
                $c['session'],
                $c['config']["security"]['secret']
            );
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

        $container['logger'] = function ($c) {
            $log = new Logger('roadiz');
            $log->pushHandler(new StreamHandler(ROADIZ_ROOT . '/logs/roadiz.log', Logger::NOTICE));

            if (null !== $c['em'] &&
                true === $c['config']['devMode']) {
                $log->pushHandler(new StreamHandler(ROADIZ_ROOT . '/logs/roadiz-debug.log', Logger::DEBUG));
            }
            if (null !== $c['em'] &&
                true !== $c['config']['install']) {
                $log->pushHandler(new DoctrineHandler(
                    $c['em'],
                    $c['securityTokenStorage'],
                    $c['request'],
                    Logger::INFO
                ));
            }

            /*
             * Add processors
             */
            $log->pushProcessor(new RequestProcessor($c['request']));
            $log->pushProcessor(new TokenStorageProcessor($c['securityTokenStorage']));

            return $log;
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

        $container['accessMap'] = function ($c) {
            return new AccessMap();
        };

        $container['userProvider'] = function ($c) {
            return new UserProvider($c['em']);
        };
        $container['userChecker'] = function ($c) {
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

        $container['tokenBasedRememberMeServices'] = function ($c) {
            return new TokenBasedRememberMeServices(
                [$c['userProvider']],
                $c['config']["security"]['secret'],
                Kernel::SECURITY_DOMAIN,
                [
                    'name' => $c['rememberMeCookieName'],
                    'lifetime' => 60 * 60 * 48,
                    'remember_me_parameter' => '_remember_me',
                    'path' => $c['request']->getBasePath(),
                    'domain' => $c['request']->getHost(),
                    'always_remember_me' => false,
                    'secure' => false,
                    'httponly' => false,
                ],
                $c['logger']
            );
        };

        $container['rememberMeListener'] = function ($c) {
            return new RememberMeListener(
                $c['securityTokenStorage'],
                $c['tokenBasedRememberMeServices'],
                $c['authentificationManager'],
                $c['logger'],
                $c['dispatcher']
            );
        };

        $container['authentificationManager'] = function ($c) {
            return new AuthenticationProviderManager([
                $c['rememberMeAuthenticationProvider'],
                $c['daoAuthenticationProvider'],
            ]);
        };

        /*
         * Main decision manager, set your voters here.
         */
        $container['accessDecisionManager'] = function ($c) {
            return new AccessDecisionManager(
                [
                    $c['roleHierarchyVoter'],
                ]
            );
        };

        $container['securityAuthorizationChecker'] = function ($c) {
            return new AuthorizationChecker(
                $c['securityTokenStorage'],
                $c['authentificationManager'],
                $c['accessDecisionManager']
            );
        };
        $container['securityTokenStorage'] = function ($c) {
            return new TokenStorage();
        };

        $container['roleHierarchy'] = function ($c) {
            return new RoleHierarchy([
                Role::ROLE_SUPERADMIN => $c['allBasicRoles'],
            ]);
        };

        $container['allBasicRoles'] = function ($c) {
            return $c['em']->getRepository('RZ\Roadiz\Core\Entities\Role')
            ->getAllBasicRoleName();
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

        $container['firewallMap'] = function ($c) {
            $map = new FirewallMap();
            return $map;
        };

        $container['firewallExceptionListener'] = function ($c) {
            return new ExceptionListener(
                $c['securityTokenStorage'],
                new AuthenticationTrustResolver('', ''),
                $c['httpUtils'],
                Kernel::SECURITY_DOMAIN,
                $c['formAuthentificationEntryPoint'],
                null,
                $c['accessDeniedHandler'],
                $c['logger']
            );
        };

        $container['formAuthentificationEntryPoint'] = function ($c) {
            return new FormAuthenticationEntryPoint(
                $c['httpKernel'],
                $c['httpUtils'],
                '/login',
                true
            );
        };

        $container['passwordEncoder'] = function ($c) {
            return new MessageDigestPasswordEncoder('sha512', true, 5000);
        };

        $container['userImplementations'] = function ($c) {
            return [
                'RZ\\Roadiz\\Core\\Entities\\User' => $c['passwordEncoder'],
            ];
        };

        $container['userEncoderFactory'] = function ($c) {
            return new EncoderFactory($c['userImplementations']);
        };

        $container['firewall'] = function ($c) {
            $c['stopwatch']->start('firewall');
            $firewall = new Firewall($c['firewallMap'], $c['dispatcher']);
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
