<?php

namespace RZ\Renzo\Core\Services;

use Pimple\Container;
use Symfony\Component\Security\Http\Firewall;
use Symfony\Component\Security\Http\FirewallMap;
use Symfony\Component\Security\Http\Firewall\ExceptionListener;
use Symfony\Component\Security\Http\Firewall\ContextListener;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolver;
use Symfony\Component\Security\Core\Authentication\Provider\DaoAuthenticationProvider;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserChecker;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManager;
use Symfony\Component\Security\Core\Authorization\Voter\RoleVoter;

use Symfony\Component\Form\Extension\Csrf\CsrfExtension;
use Symfony\Component\Form\Extension\Csrf\CsrfProvider\SessionCsrfProvider;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;

use Symfony\Component\HttpFoundation\Session\Session;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Handlers\UserProvider;
use RZ\Renzo\Core\Handlers\UserHandler;

/**
 * Register security services for dependency injection container.
 */
class SecurityServiceProvider implements \Pimple\ServiceProviderInterface
{
    public function register(Container $container)
    {
        $container['session'] = function ($c) {
            $session = new Session();
            Kernel::getInstance()->getRequest()->setSession($session);
            return $session;
        };

        $container['csrfProvider'] = function ($c) {
            $csrfSecret = $c['config']["security"]['secret'];
            return new SessionCsrfProvider(
                $c['session'],
                $csrfSecret
            );
        };

        $container['logger'] = function ($c) {
            $logger = new \RZ\Renzo\Core\Log\Logger();
            $logger->setSecurityContext($c['securityContext']);

            return $logger;
        };

        $container['contextListener'] = function ($c) {

            $c['session']; //Force session handler

            return new ContextListener(
                $c['securityContext'],
                array($c['userProvider']),
                Kernel::SECURITY_DOMAIN,
                $c['logger'],
                $c['dispatcher']
            );
        };

        $container['userProvider'] = function ($c) {
            return new UserProvider();
        };
        $container['userChecker'] = function ($c) {
            return new UserChecker();
        };
        $container['authentificationManager'] = function ($c) {
            return new DaoAuthenticationProvider(
                $c['userProvider'],
                $c['userChecker'],
                Kernel::SECURITY_DOMAIN,
                UserHandler::getEncoderFactory()
            );
        };
        $container['accessDecisionManager'] = function ($c) {
            return new AccessDecisionManager(
                array(
                    new RoleVoter('ROLE_')
                )
            );
        };
        $container['securityContext'] = function ($c) {
            return new SecurityContext(
                $c['authentificationManager'],
                $c['accessDecisionManager']
            );
        };

        $container['firewallMap'] = function ($c) {
            return new FirewallMap();
        };


        $container['firewallExceptionListener'] = function ($c) {

            return new \Symfony\Component\Security\Http\Firewall\ExceptionListener(
                $c['securityContext'],
                new \Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolver('', ''),
                $c['httpUtils'],
                Kernel::SECURITY_DOMAIN,
                new \Symfony\Component\Security\Http\EntryPoint\FormAuthenticationEntryPoint(
                    $c['httpKernel'],
                    $c['httpUtils'],
                    '/login',
                    true // bool $useForward
                ),
                null, //$errorPage
                $c['accessDeniedHandler'],
                $c['logger'] //LoggerInterface $logger
            );
        };

        /*
         * Default denied handler
         */
        $container['accessDeniedHandler'] = function ($c) {
            return new \RZ\Renzo\Core\Authorization\AccessDeniedHandler();
        };
    }
}
