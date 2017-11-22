<?php
/**
 * Copyright © 2016, Ambroise Maupate and Julien Blanchet
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
 * @file FirewallEntry.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Utils\Security;

use Pimple\Container;
use RZ\Roadiz\Core\Authentification\AuthenticationFailureHandler;
use RZ\Roadiz\Core\Authentification\AuthenticationSuccessHandler;
use RZ\Roadiz\Core\Authorization\AccessDeniedHandler;
use RZ\Roadiz\Core\Kernel;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Http\EntryPoint\FormAuthenticationEntryPoint;
use Symfony\Component\Security\Http\Firewall\AbstractAuthenticationListener;
use Symfony\Component\Security\Http\Firewall\AnonymousAuthenticationListener;
use Symfony\Component\Security\Http\Firewall\ExceptionListener;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;
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
    /**
     * @var string
     */
    protected $firewallBasePattern;
    /**
     * @var string
     */
    protected $firewallBasePath;
    /**
     * @var string
     */
    protected $firewallLogin;
    /**
     * @var string
     */
    protected $firewallLogout;
    /**
     * @var string
     */
    protected $firewallLoginCheck;
    /**
     * @var string
     */
    protected $firewallBaseRole;
    /**
     * @var Container
     */
    protected $container;
    /**
     * @var AuthenticationSuccessHandler
     */
    protected $authenticationSuccessHandler;
    /**
     * @var AuthenticationFailureHandler
     */
    protected $authenticationFailureHandler;
    /**
     * @var array
     */
    protected $listeners;
    /**
     * @var RequestMatcher
     */
    protected $requestMatcher;
    /**
     * @var boolean
     */
    protected $useReferer = false;
    /**
     * @var string
     */
    protected $authenticationSuccessHandlerClass;
    /**
     * @var string
     */
    protected $authenticationFailureHandlerClass;
    /**
     * @var AccessDeniedHandlerInterface
     */
    protected $accessDeniedHandler;
    /**
     * @var boolean
     */
    protected $locked;

    /**
     * FirewallEntry constructor.
     *
     * @param Container $container
     * @param string $firewallBasePattern
     * @param string $firewallBasePath
     * @param string|null $firewallLogin
     * @param string|null $firewallLogout
     * @param string|null $firewallLoginCheck
     * @param string|array $firewallBaseRole
     * @param string $authenticationSuccessHandlerClass
     * @param string $authenticationFailureHandlerClass
     */
    public function __construct(
        Container $container,
        $firewallBasePattern,
        $firewallBasePath,
        $firewallLogin = null,
        $firewallLogout = null,
        $firewallLoginCheck = null,
        $firewallBaseRole = 'ROLE_USER',
        $authenticationSuccessHandlerClass = 'RZ\Roadiz\Core\Authentification\AuthenticationSuccessHandler',
        $authenticationFailureHandlerClass = 'RZ\Roadiz\Core\Authentification\AuthenticationFailureHandler'
    ) {
        $this->firewallBasePattern = $firewallBasePattern;
        $this->firewallBasePath = $firewallBasePath;
        $this->firewallLogin = $firewallLogin;
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
        if (null !== $this->firewallBasePattern && "" !== $this->firewallBasePattern) {
            $this->container['accessMap']->add($this->requestMatcher, $this->firewallBaseRole);
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
            $this->container['kernel']->isDebug() ? $this->container['logger'] : null,
            $this->container['authentificationManager']
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
            $this->container['logger'],
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

    /**
     * @return ListenerInterface
     */
    protected function getAuthenticationListener()
    {
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
        $this->authenticationFailureHandler = new $this->authenticationFailureHandlerClass(
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

        return new UsernamePasswordFormAuthenticationListener(
            $this->container['securityTokenStorage'],
            $this->container['authentificationManager'],
            new SessionAuthenticationStrategy(SessionAuthenticationStrategy::MIGRATE),
            $this->container['httpUtils'],
            Kernel::SECURITY_DOMAIN,
            $this->authenticationSuccessHandler,
            $this->authenticationFailureHandler,
            [
                'check_path' => $this->firewallLoginCheck,
            ],
            $this->container['logger'],
            $this->container['dispatcher'],
            null
        );
    }

    /**
     * @param bool $useForward
     * @return AuthenticationEntryPointInterface
     */
    protected function getAuthenticationEntryPoint($useForward = false)
    {
        return new FormAuthenticationEntryPoint(
            $this->container['httpKernel'],
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
            $this->container['securityAuthentificationTrustResolver'],
            $this->container['httpUtils'],
            Kernel::SECURITY_DOMAIN,
            $this->getAuthenticationEntryPoint($useForward),
            null,
            $this->accessDeniedHandler,
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
