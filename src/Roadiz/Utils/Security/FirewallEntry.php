<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Security;

use Pimple\Container;
use RZ\Roadiz\Core\Authentication\AuthenticationFailureHandler;
use RZ\Roadiz\Core\Authentication\AuthenticationSuccessHandler;
use RZ\Roadiz\Core\Authentication\LoginAttemptAwareInterface;
use RZ\Roadiz\Core\Authentication\Manager\LoginAttemptManager;
use RZ\Roadiz\Core\Authorization\AccessDeniedHandler;
use RZ\Roadiz\Core\Bags\Settings;
use RZ\Roadiz\JWT\JwtConfigurationFactory;
use RZ\Roadiz\OpenId\Authentication\OAuth2AuthenticationListener;
use RZ\Roadiz\OpenId\Discovery;
use RZ\Roadiz\OpenId\Logout\OpenIdLogoutHandler;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\Security\Http\AccessMap;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Http\EntryPoint\FormAuthenticationEntryPoint;
use Symfony\Component\Security\Http\Firewall\AbstractAuthenticationListener;
use Symfony\Component\Security\Http\Firewall\AnonymousAuthenticationListener;
use Symfony\Component\Security\Http\Firewall\ExceptionListener;
use Symfony\Component\Security\Http\Firewall\LogoutListener;
use Symfony\Component\Security\Http\Firewall\UsernamePasswordFormAuthenticationListener;
use Symfony\Component\Security\Http\Logout\DefaultLogoutSuccessHandler;
use Symfony\Component\Security\Http\Logout\SessionLogoutHandler;
use Symfony\Component\Security\Http\Session\SessionAuthenticationStrategy;

/**
 * FirewallEntry automatize firewall and access-map configuration with
 * a classic form entry-point.
 *
 * @package RZ\Roadiz\Utils\Security
 */
class FirewallEntry
{
    protected string $firewallBasePattern;
    protected string $firewallBasePath;
    protected ?string $firewallLogin;
    protected ?string $firewallLogout;
    protected ?string $firewallAfterLogout;
    protected ?string $firewallLoginCheck;
    /**
     * @var array<string>
     */
    protected array $firewallBaseRole;
    protected Container $container;
    protected ?AuthenticationSuccessHandler $authenticationSuccessHandler = null;
    protected ?AuthenticationFailureHandler $authenticationFailureHandler = null;
    protected array $listeners;
    protected RequestMatcher $requestMatcher;
    protected bool $useReferer = false;
    protected string $authenticationSuccessHandlerClass;
    protected string $authenticationFailureHandlerClass;
    protected ?AccessDeniedHandlerInterface $accessDeniedHandler = null;
    protected bool $locked = false;
    protected string $providerKey;

    /**
     * @param Container $container
     * @param string $firewallBasePattern
     * @param string $firewallBasePath
     * @param string|null $firewallLogin
     * @param string|null $firewallLogout
     * @param string|null $firewallLoginCheck
     * @param string|array $firewallBaseRole
     * @param string $authenticationSuccessHandlerClass
     * @param string $authenticationFailureHandlerClass
     * @param string $providerKey
     */
    public function __construct(
        Container $container,
        string $firewallBasePattern,
        string $firewallBasePath,
        ?string $firewallLogin = null,
        ?string $firewallLogout = null,
        ?string $firewallLoginCheck = null,
        $firewallBaseRole = 'ROLE_USER',
        string $authenticationSuccessHandlerClass = AuthenticationSuccessHandler::class,
        string $authenticationFailureHandlerClass = AuthenticationFailureHandler::class,
        string $providerKey = 'roadiz_domain'
    ) {
        $this->firewallBasePattern = $firewallBasePattern;
        $this->firewallBasePath = $firewallBasePath;
        $this->firewallLogin = $firewallLogin;
        $this->providerKey = $providerKey;
        // Default, use login path to redirect user after logout.
        $this->firewallAfterLogout = $firewallLogin;
        $this->firewallLogout = $firewallLogout;
        $this->firewallLoginCheck = $firewallLoginCheck;
        $this->accessDeniedHandler = null;
        $this->container = $container;
        $this->authenticationSuccessHandlerClass = $authenticationSuccessHandlerClass;
        $this->authenticationFailureHandlerClass = $authenticationFailureHandlerClass;
        $this->requestMatcher = new RequestMatcher($this->firewallBasePattern);

        if (is_array($firewallBaseRole)) {
            $this->firewallBaseRole = $firewallBaseRole;
        } else {
            $this->firewallBaseRole = [$firewallBaseRole];
        }

        /*
         * Add an access map entry only if basePath pattern is valid and
         * not root level.
         */
        /** @var AccessMap $accessMap */
        $accessMap = $this->container['accessMap'];
        if (null !== $this->firewallBasePattern && "" !== $this->firewallBasePattern) {
            $accessMap->add($this->requestMatcher, $this->firewallBaseRole);
        }

        $this->listeners = [
            // manages the SecurityContext persistence through a session
            [$this->container['contextListener'], 0],
            [$this->container['rememberMeListener'], 10],
        ];
    }

    /**
     * Added anonymous listener to enable all visitor to
     * access your firewall entry base pattern.
     *
     * Warning: this MUST be the before last listener to work.
     *
     * @return $this
     */
    public function withAnonymousAuthenticationListener()
    {
        $this->listeners[] = [new AnonymousAuthenticationListener(
            $this->container['securityTokenStorage'],
            $this->container['config']['security']['secret'],
            $this->container['kernel']->isDebug() ? $this->container['logger.security'] : null,
            $this->container['authenticationManager']
        ), 8888];
        return $this;
    }

    /**
     * @param string $redirectRoute
     * @param array $redirectParameters
     * @return $this
     */
    public function withAccessDeniedHandler($redirectRoute = '', $redirectParameters = [])
    {
        $this->accessDeniedHandler = new AccessDeniedHandler(
            $this->container['urlGenerator'],
            $this->container['logger.security'],
            $redirectRoute,
            $redirectParameters
        );
        return $this;
    }

    /**
     * @return $this
     */
    public function withSwitchUserListener()
    {
        $this->listeners[] = [$this->container["switchUser"], 2];
        return $this;
    }

    /**
     * @param array $roles
     *
     * @return $this
     */
    public function withOAuth2AuthenticationListener(array $roles = ['ROLE_USER'])
    {
        /** @var Settings $settingsBag */
        $settingsBag = $this->container['settingsBag'];
        /** @var Discovery|null $discovery */
        $discovery = $this->container[Discovery::class];
        /** @var JwtConfigurationFactory $jwtConfigurationFactory */
        $jwtConfigurationFactory = $this->container[JwtConfigurationFactory::class];
        if (null !== $discovery &&
            !empty($settingsBag->get('oauth_client_id')) &&
            !empty($settingsBag->get('oauth_client_secret'))) {
            $this->listeners[] = [
                new OAuth2AuthenticationListener(
                    $this->container['securityTokenStorage'],
                    $this->container['authenticationManager'],
                    new SessionAuthenticationStrategy(SessionAuthenticationStrategy::MIGRATE),
                    $this->container['httpUtils'],
                    $this->providerKey,
                    $this->getAuthenticationSuccessHandler(),
                    $this->getAuthenticationFailureHandler(),
                    $this->container['csrfTokenManager'],
                    $discovery,
                    $jwtConfigurationFactory->create(),
                    [
                        'check_path' => $this->firewallLoginCheck,
                        'oauth_client_id' => $settingsBag->get('oauth_client_id'),
                        'oauth_client_secret' => $settingsBag->get('oauth_client_secret'),
                        'username_claim' => $settingsBag->get('openid_username_claim', 'email'),
                        'roles' => $roles
                    ],
                    $this->container['logger.security'],
                    $this->container['proxy.dispatcher']
                ),
                20
            ];
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function withReferer()
    {
        $this->useReferer = true;

        return $this;
    }

    /**
     * @return RequestMatcher
     */
    public function getRequestMatcher()
    {
        return $this->requestMatcher;
    }

    /**
     * @return bool
     */
    public function hasAuthenticationEntryPoints()
    {
        return null !== $this->firewallLogin &&
                null !== $this->firewallLogout &&
                null !== $this->firewallLoginCheck;
    }

    /**
     * @return AbstractAuthenticationListener[]
     */
    public function getListeners()
    {
        if (!$this->locked) {
            if ($this->hasAuthenticationEntryPoints()) {
                // logout users
                $this->listeners[] = [$this->getLogoutListener(), 1];
                $this->listeners[] = [$this->getAuthenticationListener(), 20];
                // Warning: this MUST be the last listener to work.
                $this->listeners[] = [$this->container['securityAccessListener'], 9999];
            }

            /*
             * Sort listeners by priority
             */
            usort($this->listeners, function ($a, $b) {
                return ($a[1] > $b[1]) ? 1 : 0;
            });
            $this->locked = true;
        }

        return array_map(function ($a) {
            return $a[0];
        }, $this->listeners);
    }

    protected function getAuthenticationSuccessHandler()
    {
        if (null === $this->authenticationSuccessHandler) {
            $this->authenticationSuccessHandler = new $this->authenticationSuccessHandlerClass(
                $this->container['httpUtils'],
                $this->container['em'],
                $this->container['tokenBasedRememberMeServices'],
                [
                    'always_use_default_target_path' => false,
                    'default_target_path' => $this->firewallBasePath,
                    'login_path' => $this->firewallLogin,
                    'target_path_parameter' => '_target_path',
                    'use_referer' => $this->useReferer,
                ]
            );
            if ($this->authenticationSuccessHandler instanceof LoginAttemptAwareInterface) {
                $this->authenticationSuccessHandler->setLoginAttemptManager(
                    $this->container[LoginAttemptManager::class]
                );
            }
        }
        return $this->authenticationSuccessHandler;
    }

    protected function getAuthenticationFailureHandler()
    {
        if (null === $this->authenticationFailureHandler) {
            $this->authenticationFailureHandler = new $this->authenticationFailureHandlerClass(
                $this->container['proxy.httpKernel'],
                $this->container['httpUtils'],
                [
                    'failure_path' => $this->firewallLogin,
                    'failure_forward' => false,
                    'login_path' => $this->firewallLogin,
                    'failure_path_parameter' => '_failure_path',
                ],
                $this->container['logger.security']
            );

            if ($this->authenticationFailureHandler instanceof LoginAttemptAwareInterface) {
                $this->authenticationFailureHandler->setLoginAttemptManager(
                    $this->container[LoginAttemptManager::class]
                );
            }
        }
        return $this->authenticationFailureHandler;
    }

    protected function getAuthenticationListener()
    {
        return new UsernamePasswordFormAuthenticationListener(
            $this->container['securityTokenStorage'],
            $this->container['authenticationManager'],
            new SessionAuthenticationStrategy(SessionAuthenticationStrategy::MIGRATE),
            $this->container['httpUtils'],
            $this->providerKey,
            $this->getAuthenticationSuccessHandler(),
            $this->getAuthenticationFailureHandler(),
            [
                'check_path' => $this->firewallLoginCheck,
            ],
            $this->container['logger.security'],
            $this->container['proxy.dispatcher'],
            null
        );
    }

    /**
     * @param bool $useForward
     * @return AuthenticationEntryPointInterface|null
     */
    protected function getAuthenticationEntryPoint($useForward = false)
    {
        if (null === $this->firewallLogin) {
            return null;
        }
        return new FormAuthenticationEntryPoint(
            $this->container['proxy.httpKernel'],
            $this->container['httpUtils'],
            $this->firewallLogin,
            $useForward
        );
    }

    /**
     * @param bool $useForward Use true to forward request instead of redirecting. Be careful, Token will be set to null
     * in sub-request!
     * @return ExceptionListener
     */
    public function getExceptionListener($useForward = false)
    {
        return new ExceptionListener(
            $this->container['securityTokenStorage'],
            $this->container['securityAuthenticationTrustResolver'],
            $this->container['httpUtils'],
            $this->providerKey,
            $this->getAuthenticationEntryPoint($useForward),
            null,
            $this->accessDeniedHandler,
            $this->container['logger.security']
        );
    }

    /**
     * @return LogoutListener
     */
    protected function getLogoutListener()
    {
        /*
         * Logout listener
         */
        $logoutListener = new LogoutListener(
            $this->container['securityTokenStorage'],
            $this->container['httpUtils'],
            new DefaultLogoutSuccessHandler(
                $this->container['httpUtils'],
                $this->firewallAfterLogout
            ),
            [
                'logout_path' => $this->firewallLogout,
            ]
        );
        $logoutListener->addHandler(new SessionLogoutHandler());
        // Cancel remember me token
        $logoutListener->addHandler($this->container['tokenBasedRememberMeServices']);

        /** @var Discovery|null $discovery */
        $discovery = $this->container[Discovery::class];
        if (null !== $discovery) {
            $logoutListener->addHandler(new OpenIdLogoutHandler($discovery));
        }
        $logoutListener->addHandler($this->container['cookieClearingLogoutHandler']);

        return $logoutListener;
    }

    /**
     * @return string
     */
    public function getFirewallAfterLogout(): string
    {
        return $this->firewallAfterLogout;
    }

    /**
     * @param string $firewallAfterLogout
     *
     * @return FirewallEntry
     */
    public function setFirewallAfterLogout(string $firewallAfterLogout): FirewallEntry
    {
        $this->firewallAfterLogout = $firewallAfterLogout;

        return $this;
    }
}
