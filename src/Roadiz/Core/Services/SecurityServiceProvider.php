<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Services;

use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\Persistence\ManagerRegistry;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\Roadiz\Core\Authentication\Manager\LoginAttemptManager;
use RZ\Roadiz\Core\Authentication\Provider\AttemptAwareDaoAuthenticationProvider;
use RZ\Roadiz\Core\Authorization\AccessDeniedHandler;
use RZ\Roadiz\Core\Authorization\Chroot\NodeChrootChainResolver;
use RZ\Roadiz\Core\Authorization\Chroot\NodeChrootResolver;
use RZ\Roadiz\Core\Authorization\Chroot\RoadizUserNodeChrootResolver;
use RZ\Roadiz\Core\Authorization\Voter\GroupVoter;
use RZ\Roadiz\Core\Entities\Role;
use RZ\Roadiz\Core\Entities\User;
use RZ\Roadiz\Core\Exceptions\NoConfigurationFoundException;
use RZ\Roadiz\Core\Handlers\UserProvider;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Utils\Security\DoctrineRoleHierarchy;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Security\Core\Authentication\AuthenticationProviderManager;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolver;
use Symfony\Component\Security\Core\Authentication\Provider\AnonymousAuthenticationProvider;
use Symfony\Component\Security\Core\Authentication\Provider\RememberMeAuthenticationProvider;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\RememberMeToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManager;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Core\Authorization\Voter\RoleHierarchyVoter;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;
use Symfony\Component\Security\Core\User\ChainUserProvider;
use Symfony\Component\Security\Core\User\UserChecker;
use Symfony\Component\Security\Core\User\UserProviderInterface;
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
        $container['session.pdo'] = function (Container $c) {
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

        $container['session_storage'] = function (Container $c) {
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

        $container['session'] = function (Container $c) {
            /** @var RequestStack $requestStack */
            $requestStack = $c['requestStack'];
            $request = $requestStack->getCurrentRequest();
            $session = new Session($c['session_storage']);
            if (null !== $request) {
                $request->setSession($session);
            }
            return $session;
        };

        /*
         * Required for HttpKernel AbstractSessionListener
         */
        $container['initialized_session'] = function (Container $c) {
            /** @var RequestStack $requestStack */
            $requestStack = $c['requestStack'];
            $request = $requestStack->getMasterRequest();
            if (null !== $request && $request->hasSession()) {
                return $request->getSession();
            }

            return null;
        };

        $container['sessionTokenStorage'] = function (Container $c) {
            return new SessionTokenStorage(
                $c['session'],
                $c['config']["security"]['secret']
            );
        };

        $container['csrfTokenManager'] = function (Container $c) {
            return new CsrfTokenManager(
                new UriSafeTokenGenerator(),
                $c['sessionTokenStorage']
            );
        };

        $container['securityAuthenticationUtils'] = function (Container $c) {
            return new AuthenticationUtils($c['requestStack']);
        };

        $container['contextListener'] = function (Container $c) {
            return new ContextListener(
                $c['securityTokenStorage'],
                $c['userProviders'],
                Kernel::SECURITY_DOMAIN,
                $c['logger.security'],
                $c['proxy.dispatcher']
            );
        };

        $container[UserProviderInterface::class] = function (Container $c) {
            return new ChainUserProvider($c['userProviders']);
        };

        /*
         * userProviders should be extendable to add new UserProviderInterface implementation
         * if we add external IdentityProvider to expose private Roadiz content
         */
        $container['userProviders'] = function (Container $c) {
            return [
                $c[UserProvider::class],
            ];
        };

        $container['accessMap'] = function () {
            return new AccessMap();
        };

        $container[UserProvider::class] = function (Container $c) {
            return new UserProvider($c[ManagerRegistry::class]);
        };

        $container['userChecker'] = function () {
            return new UserChecker();
        };

        $container[LoginAttemptManager::class] = function (Container $c) {
            return new LoginAttemptManager($c['requestStack'], $c[ManagerRegistry::class], $c['logger']);
        };

        $container['daoAuthenticationProvider'] = function (Container $c) {
            return new AttemptAwareDaoAuthenticationProvider(
                $c[LoginAttemptManager::class],
                $c[UserProviderInterface::class],
                $c['userChecker'],
                Kernel::SECURITY_DOMAIN,
                $c['userEncoderFactory']
            );
        };

        $container['rememberMeAuthenticationProvider'] = function (Container $c) {
            return new RememberMeAuthenticationProvider(
                $c['userChecker'],
                $c['config']["security"]['secret'],
                Kernel::SECURITY_DOMAIN
            );
        };

        $container['rememberMeCookieName'] = 'roadiz_remember_me';
        $container['rememberMeCookieLifetime'] = function (Container $c) {
            if (isset($c['config']['rememberMeLifetime'])) {
                return (int) $c['config']['rememberMeLifetime'];
            } else {
                // One month long cookie
                return 60 * 60 * 24 * 30;
            }
        };

        $container['cookieClearingLogoutHandler'] = function (Container $c) {
            /** @var RequestContext $requestContext */
            $requestContext = $c['requestContext'];
            return new CookieClearingLogoutHandler([
                $c['rememberMeCookieName'] => [
                    'path' => $requestContext->getBaseUrl(),
                    'domain' => $requestContext->getHost(),
                ],
            ]);
        };

        $container['tokenBasedRememberMeServices'] = function (Container $c) {
            /** @var RequestContext $requestContext */
            $requestContext = $c['requestContext'];
            return new TokenBasedRememberMeServices(
                $c['userProviders'],
                $c['config']["security"]['secret'],
                Kernel::SECURITY_DOMAIN,
                [
                    'name' => $c['rememberMeCookieName'],
                    'lifetime' => $c['rememberMeCookieLifetime'],
                    'remember_me_parameter' => '_remember_me',
                    'path' => $requestContext->getBaseUrl(),
                    'domain' => $requestContext->getHost(),
                    'always_remember_me' => false,
                    'httponly' => $c['config']["security"]['session_cookie_httponly'],
                ],
                $c['logger.security']
            );
        };

        $container['rememberMeListener'] = function (Container $c) {
            return new RememberMeListener(
                $c['securityTokenStorage'],
                $c['tokenBasedRememberMeServices'],
                $c['authenticationManager'],
                $c['logger.security'],
                $c['proxy.dispatcher']
            );
        };

        $container['authenticationProviderList'] = function (Container $c) {
            return [
                new AnonymousAuthenticationProvider($c['config']["security"]['secret']),
                $c['rememberMeAuthenticationProvider'],
                $c['daoAuthenticationProvider'],
            ];
        };

        $container['authenticationManager'] = function (Container $c) {
            return new AuthenticationProviderManager($c['authenticationProviderList']);
        };

        $container['authentificationManager'] = function (Container $c) {
            return $c['authenticationManager'];
        };

        $container['security.voters'] = function (Container $c) {
            return [
                new AuthenticatedVoter($c['securityAuthenticationTrustResolver']),
                $c['roleHierarchyVoter'],
                $c['groupVoter'],
            ];
        };

        /*
         * Main decision manager, set your voters here.
         */
        $container['accessDecisionManager'] = function (Container $c) {
            return new AccessDecisionManager($c['security.voters']);
        };


        $container['securityAuthenticationTrustResolver'] = function () {
            return new AuthenticationTrustResolver(
                AnonymousToken::class,
                RememberMeToken::class
            );
        };
        $container['securityAuthentificationTrustResolver'] = function (Container $c) {
            return $c['securityAuthenticationTrustResolver'];
        };

        /*
         * Alias with FQN interface
         */
        $container[AuthorizationCheckerInterface::class] = function (Container $c) {
            return $c['securityAuthorizationChecker'];
        };

        $container['securityAuthorizationChecker'] = function (Container $c) {
            return new AuthorizationChecker(
                $c['securityTokenStorage'],
                $c['authenticationManager'],
                $c['accessDecisionManager']
            );
        };

        $container['securityTokenStorage'] = function () {
            return new TokenStorage();
        };

        $container['securityAccessListener'] = function (Container $c) {
            return new AccessListener(
                $c['securityTokenStorage'],
                $c['accessDecisionManager'],
                $c['accessMap'],
                $c['authenticationManager']
            );
        };

        $container['roleHierarchy'] = function (Container $c) {
            try {
                /** @var Kernel $kernel */
                $kernel = $c['kernel'];
                if ($kernel->isInstallMode()) {
                    return new DoctrineRoleHierarchy();
                }
                return new DoctrineRoleHierarchy($c[ManagerRegistry::class]);
            } catch (ConnectionException $e) {
                /*
                 * Do not use DB roles when DB is not reachable
                 */
                return new DoctrineRoleHierarchy();
            } catch (TableNotFoundException $e) {
                /*
                 * Do not use DB roles when DB tables are not created
                 */
                return new DoctrineRoleHierarchy();
            }
        };

        $container['roleHierarchyVoter'] = function (Container $c) {
            return new RoleHierarchyVoter($c['roleHierarchy']);
        };

        $container['groupVoter'] = function (Container $c) {
            return new GroupVoter($c['roleHierarchy']);
        };

        $container["switchUser"] = function (Container $c) {
            return new SwitchUserListener(
                $c['securityTokenStorage'],
                $c[UserProviderInterface::class],
                $c['userChecker'],
                $c['config']["security"]['secret'],
                $c['accessDecisionManager'],
                $c['logger.security'],
                '_su',
                Role::ROLE_SUPERADMIN,
                $c['proxy.dispatcher']
            );
        };

        $container['firewallMap'] = function () {
            return new FirewallMap();
        };

        $container['passwordEncoder'] = function (Container $c) {
            return $c['config']['security'];
        };

        $container['userImplementations'] = function (Container $c) {
            return [
                User::class => $c['passwordEncoder'],
            ];
        };

        $container['userEncoderFactory'] = function (Container $c) {
            return new EncoderFactory($c['userImplementations']);
        };

        /*
         * Default denied handler
         */
        $container['accessDeniedHandler'] = function (Container $c) {
            return new AccessDeniedHandler($c['urlGenerator'], $c['logger.security']);
        };

        $container['nodeChrootResolvers'] = function (Container $c) {
            return [
                new RoadizUserNodeChrootResolver(),
            ];
        };

        $container[NodeChrootResolver::class] = function (Container $c) {
            return new NodeChrootChainResolver($c['nodeChrootResolvers']);
        };

        return $container;
    }
}
