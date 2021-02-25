<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Security;

use Pimple\Container;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Http\EntryPoint\BasicAuthenticationEntryPoint;
use Symfony\Component\Security\Http\Firewall\AbstractAuthenticationListener;
use Symfony\Component\Security\Http\Firewall\BasicAuthenticationListener;
use Symfony\Component\Security\Http\Firewall\ExceptionListener;

/**
 * BasicFirewallEntry automatize firewall and access-map configuration with
 * a Basic Auth entry point.
 *
 * @package RZ\Roadiz\Utils\Security
 */
class BasicFirewallEntry extends FirewallEntry
{
    /**
     * @param Container $container
     * @param string $firewallBasePattern
     * @param string $firewallBasePath
     * @param string $firewallBaseRole
     */
    public function __construct(
        Container $container,
        string $firewallBasePattern,
        string $firewallBasePath,
        string $firewallBaseRole = 'ROLE_USER'
    ) {
        parent::__construct(
            $container,
            $firewallBasePattern,
            $firewallBasePath,
            null,
            null,
            null,
            $firewallBaseRole
        );
        $this->listeners = [];
    }

    protected function getAuthenticationListener()
    {
        $this->authenticationSuccessHandler = null;
        $this->authenticationFailureHandler = null;

        return new BasicAuthenticationListener(
            $this->container['securityTokenStorage'],
            $this->container['authenticationManager'],
            $this->providerKey,
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
            $this->providerKey
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
            $this->providerKey,
            $this->getAuthenticationEntryPoint(),
            null,
            $this->accessDeniedHandler,
            $this->container['logger'],
            false
        );
    }
}
