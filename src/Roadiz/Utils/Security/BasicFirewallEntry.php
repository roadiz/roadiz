<?php
/**
 * Copyright (c) 2017. Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
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
 * @file BasicFirewallEntry.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace RZ\Roadiz\Utils\Security;

use Pimple\Container;
use RZ\Roadiz\Core\Kernel;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Http\EntryPoint\BasicAuthenticationEntryPoint;
use Symfony\Component\Security\Http\Firewall\AbstractAuthenticationListener;
use Symfony\Component\Security\Http\Firewall\BasicAuthenticationListener;
use Symfony\Component\Security\Http\Firewall\ExceptionListener;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;

/**
 * BasicFirewallEntry automatize firewall and access-map configuration with
 * a Basic Auth entry point.
 *
 * @package RZ\Roadiz\Utils\Security
 */
class BasicFirewallEntry extends FirewallEntry
{
    /**
     * BasicFirewallEntry constructor.
     *
     * @param Container $container
     * @param string $firewallBasePattern
     * @param string $firewallBasePath
     * @param string $firewallBaseRole
     */
    public function __construct(
        Container $container,
        $firewallBasePattern,
        $firewallBasePath,
        $firewallBaseRole = 'ROLE_USER'
    ) {
        $this->container = $container;
        $this->firewallBasePattern = $firewallBasePattern;
        $this->firewallBasePath = $firewallBasePath;
        $this->firewallLogin = null;
        $this->firewallLogout = null;
        $this->firewallLoginCheck = null;
        $this->accessDeniedHandler = null;

        if (is_array($firewallBaseRole)) {
            $this->firewallBaseRole = $firewallBaseRole;
        } else {
            $this->firewallBaseRole = [$firewallBaseRole];
        }

        $this->requestMatcher = new RequestMatcher($this->firewallBasePattern);
        /*
         * Add an access map entry only if basePath pattern is valid and
         * not root level.
         */
        if (null !== $this->firewallBasePattern && "" !== $this->firewallBasePattern) {
            $this->container['accessMap']->add($this->requestMatcher, $this->firewallBaseRole);
        }
    }

    /**
     * @return ListenerInterface
     */
    protected function getAuthenticationListener()
    {
        $this->authenticationSuccessHandler = null;
        $this->authenticationFailureHandler = null;

        return new BasicAuthenticationListener(
            $this->container['securityTokenStorage'],
            $this->container['authenticationManager'],
            Kernel::SECURITY_DOMAIN,
            $this->getAuthenticationEntryPoint(),
            $this->container['logger']
        );
    }

    /**
     * @param bool $useForward
     * @return AuthenticationEntryPointInterface
     */
    protected function getAuthenticationEntryPoint($useForward = false)
    {
        return new BasicAuthenticationEntryPoint(
            Kernel::SECURITY_DOMAIN
        );
    }

    /**
     * @return AbstractAuthenticationListener[]
     */
    public function getListeners()
    {
        return [
            $this->container['contextListener'],
            $this->getAuthenticationListener(),
            $this->container['securityAccessListener']
        ];
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
            Kernel::SECURITY_DOMAIN,
            $this->getAuthenticationEntryPoint(),
            null,
            $this->accessDeniedHandler,
            $this->container['logger'],
            false
        );
    }
}
