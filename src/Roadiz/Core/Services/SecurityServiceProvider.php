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
use RZ\Roadiz\Core\Authorization\AccessDeniedHandler;
use RZ\Roadiz\Core\Handlers\UserProvider;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\Log\Logger;
use Symfony\Component\Form\Extension\Csrf\CsrfProvider\SessionCsrfProvider;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolver;
use Symfony\Component\Security\Core\Authentication\Provider\DaoAuthenticationProvider;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManager;
use Symfony\Component\Security\Core\Authorization\Voter\RoleHierarchyVoter;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;
use Symfony\Component\Security\Core\Role\RoleHierarchy;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Security\Core\User\UserChecker;
use Symfony\Component\Security\Http\EntryPoint\FormAuthenticationEntryPoint;
use Symfony\Component\Security\Http\Firewall;
use Symfony\Component\Security\Http\FirewallMap;
use Symfony\Component\Security\Http\Firewall\ContextListener;
use Symfony\Component\Security\Http\Firewall\ExceptionListener;
use Symfony\Component\Security\Http\Firewall\SwitchUserListener;

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
            $logger = new Logger();
            $logger->setSecurityContext($c['securityContext']);

            return $logger;
        };

        $container['contextListener'] = function ($c) {

            $c['session']; //Force session handler

            return new ContextListener(
                $c['securityContext'],
                [
                    $c['userProvider'],
                ],
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
                $c['userEncoderFactory']
            );
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
        $container['securityContext'] = function ($c) {
            return new SecurityContext(
                $c['authentificationManager'],
                $c['accessDecisionManager']
            );
        };

        $container['roleHierarchy'] = function ($c) {
            return new RoleHierarchy([
                'ROLE_SUPERADMIN' => $c['allBasicRoles'],
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
                $c['securityContext'],
                $c['userProvider'],
                $c['userChecker'],
                $c['config']["security"]['secret'],
                $c['accessDecisionManager'],
                $c['logger'],
                '_su',
                'ROLE_SUPERADMIN',
                $c['dispatcher']
            );
        };

        $container['firewallMap'] = function ($c) {
            return new FirewallMap();
        };

        $container['firewallExceptionListener'] = function ($c) {

            return new ExceptionListener(
                $c['securityContext'],
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
                'Symfony\\Component\\Security\\Core\\User\\User' => $c['passwordEncoder'],
                'RZ\\Roadiz\\Core\\Entities\\User' => $c['passwordEncoder'],
            ];
        };

        $container['userEncoderFactory'] = function ($c) {
            return new EncoderFactory($c['userImplementations']);
        };

        $container['firewall'] = function ($c) {

            // Register back-end security scheme
            $beClass = $c['backendClass'];
            $beClass::setupDependencyInjection($c);

            // Register front-end security scheme
            foreach ($c['frontendThemes'] as $theme) {
                $feClass = $theme->getClassName();
                $feClass::setupDependencyInjection($c);
            }
            $c['stopwatch']->stop('initThemes');

            $c['stopwatch']->start('firewall');
            $firewall = new Firewall($c['firewallMap'], $c['dispatcher']);
            $c['stopwatch']->stop('firewall');

            return $firewall;
        };

        /*
         * Default denied handler
         */
        $container['accessDeniedHandler'] = function ($c) {
            return new AccessDeniedHandler();
        };

        return $container;
    }
}
