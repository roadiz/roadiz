<?php
/**
 * Copyright (c) Rezo Zero 2016.
 *
 * prison-insider
 *
 * Created on 18/03/16 12:00
 *
 * @author ambroisemaupate
 * @file FirewallEntry.php
 */

namespace RZ\Roadiz\Utils\Security;

use Pimple\Container;
use RZ\Roadiz\Core\Authentification\AuthenticationFailureHandler;
use RZ\Roadiz\Core\Authentification\AuthenticationSuccessHandler;
use RZ\Roadiz\Core\Kernel;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolver;
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
 * Class FirewallEntry
 * @package RZ\Roadiz\Utils\Security
 */
class FirewallEntry
{
    /**
     * @var string
     */
    private $firewallBasePattern;
    /**
     * @var string
     */
    private $firewallBasePath;
    /**
     * @var string
     */
    private $firewallLogin;
    /**
     * @var string
     */
    private $firewallLogout;
    /**
     * @var string
     */
    private $firewallLoginCheck;
    /**
     * @var string
     */
    private $firewallBaseRole;
    /**
     * @var Container
     */
    private $container;
    /**
     * @var AuthenticationSuccessHandler
     */
    private $authenticationSuccessHandler;
    /**
     * @var AuthenticationFailureHandler
     */
    private $authenticationFailureHandler;
    /**
     * @var array
     */
    private $listeners;
    /**
     * @var RequestMatcher
     */
    private $requestMatcher;

    /**
     * FirewallEntry constructor.
     *
     * @param Container $container
     * @param string $firewallBasePattern
     * @param string $firewallBasePath
     * @param string $firewallLogin
     * @param string $firewallLogout
     * @param string $firewallLoginCheck
     * @param string $firewallBaseRole
     * @param string $authenticationSuccessHandlerClass
     * @param string $authenticationFailureHandlerClass
     */
    public function __construct(
        Container $container,
        $firewallBasePattern,
        $firewallBasePath,
        $firewallLogin,
        $firewallLogout,
        $firewallLoginCheck,
        $firewallBaseRole = 'ROLE_USER',
        $authenticationSuccessHandlerClass = 'RZ\Roadiz\Core\Authentification\AuthenticationSuccessHandler',
        $authenticationFailureHandlerClass = 'RZ\Roadiz\Core\Authentification\AuthenticationFailureHandler'
    ) {
        $this->firewallBasePattern = $firewallBasePattern;
        $this->firewallBasePath = $firewallBasePath;
        $this->firewallLogin = $firewallLogin;
        $this->firewallLogout = $firewallLogout;
        $this->firewallLoginCheck = $firewallLoginCheck;
        $this->firewallBaseRole = $firewallBaseRole;
        $this->container = $container;

        $this->requestMatcher = new RequestMatcher($this->firewallBasePattern);
        $this->container['accessMap']->add($this->requestMatcher, [$firewallBaseRole]);

        $this->authenticationSuccessHandler = new $authenticationSuccessHandlerClass(
            $this->container['httpUtils'],
            $this->container['em'],
            $this->container['tokenBasedRememberMeServices'],
            [
                'always_use_default_target_path' => false,
                'default_target_path' => $this->firewallBasePath,
                'login_path' => $this->firewallLogin,
                'target_path_parameter' => '_target_path',
                'use_referer' => false,
            ]
        );
        $this->authenticationFailureHandler = new $authenticationFailureHandlerClass(
            $this->container['httpKernel'],
            $this->container['httpUtils'],
            [
                'failure_path' => $this->firewallLogin,
                'failure_forward' => false,
                'login_path' => $this->firewallLogin,
                'failure_path_parameter' => '_failure_path',
            ],
            $this->container['logger']
        );
        $this->listeners = [
            // manages the SecurityContext persistence through a session
            $this->container['contextListener'],
            // logout users
            $this->getLogoutListener(),
            $this->container['rememberMeListener'],
            // authentication via a simple form composed of a username and a password
            new UsernamePasswordFormAuthenticationListener(
                $this->container['securityTokenStorage'],
                $this->container['authentificationManager'],
                new SessionAuthenticationStrategy(SessionAuthenticationStrategy::MIGRATE),
                $this->container['httpUtils'],
                Kernel::SECURITY_DOMAIN,
                $this->authenticationSuccessHandler,
                $this->authenticationFailureHandler,
                [
                    'check_path' => $firewallLoginCheck,
                ],
                $this->container['logger'],
                $this->container['dispatcher'],
                null
            ),
            $this->container['securityAccessListener'],
        ];
    }

    /**
     * @return $this
     */
    public function withAnonymousAuthenticationListener()
    {
        $this->listeners[] = new AnonymousAuthenticationListener($this->container['securityTokenStorage'], '');
        return $this;
    }

    public function withSwitchUserListener()
    {
        $this->listeners[] = $this->container["switchUser"];
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
     * @return AbstractAuthenticationListener[]
     */
    public function getListeners()
    {
        return $this->listeners;
    }

    /**
     * @param bool $useForward Use true to forward request instead of redirecting. Be careful, Token will be set to null
     * in sub-request!
     * @return ExceptionListener
     */
    public function getExceptionListener($useForward = false)
    {
        $formEntryPoint = new FormAuthenticationEntryPoint(
            $this->container['httpKernel'],
            $this->container['httpUtils'],
            $this->firewallLogin,
            $useForward
        );

        return new ExceptionListener(
            $this->container['securityTokenStorage'],
            new AuthenticationTrustResolver('', ''),
            $this->container['httpUtils'],
            Kernel::SECURITY_DOMAIN,
            $formEntryPoint,
            null,
            null,
            $this->container['logger']
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
                $this->firewallLogin
            ),
            [
                'logout_path' => $this->firewallLogout,
            ]
        );
        $logoutListener->addHandler(new SessionLogoutHandler());
        $logoutListener->addHandler($this->container['cookieClearingLogoutHandler']);

        return $logoutListener;
    }
}
