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
 * @file BackendController.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\CMS\Controllers;

use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\Entities\Role;
use RZ\Roadiz\Core\Authentification\AuthenticationSuccessHandler;
use Pimple\Container;

use Symfony\Component\HttpFoundation\RequestMatcher;

use Symfony\Component\Security\Http\Firewall;
use Symfony\Component\Security\Http\FirewallMap;
use Symfony\Component\Security\Http\Firewall\UsernamePasswordFormAuthenticationListener;
use Symfony\Component\Security\Http\Firewall\LogoutListener;
use Symfony\Component\Security\Http\Firewall\AccessListener;
use Symfony\Component\Security\Http\Session\SessionAuthenticationStrategy;
use Symfony\Component\Security\Http\AccessMap;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationFailureHandler;
use Symfony\Component\Security\Http\Logout\DefaultLogoutSuccessHandler;

/**
 * Special controller app file for backend themes.
 *
 * This AppController implementation will use a security scheme
 */
class BackendController extends AppController
{
    protected static $backendTheme = true;

    /**
     * Append objects to global container.
     *
     * @param Pimple\Container $container
     */
    public static function setupDependencyInjection(Container $container)
    {
        $container->extend('firewallMap', function (FirewallMap $map, $c) {

            /*
             * Prepare app firewall
             */
            $requestMatcher = new RequestMatcher('^/rz-admin');
            // allows configuration of different access control rules for specific parts of the website.
            $accessMap = new AccessMap($requestMatcher, array(
                Role::ROLE_BACKEND_USER,
                Role::ROLE_SUPERADMIN
            ));

            /*
             * Listener
             */
            $logoutListener = new LogoutListener(
                $c['securityContext'],
                $c['httpUtils'],
                new DefaultLogoutSuccessHandler($c['httpUtils'], '/login'),
                array(
                    'logout_path'    => '/rz-admin/logout',
                )
            );
            //Symfony\Component\Security\Http\Logout\SessionLogoutHandler
            $logoutListener->addHandler(new \Symfony\Component\Security\Http\Logout\SessionLogoutHandler());

            $listeners = array(
                // manages the SecurityContext persistence through a session
                $c['contextListener'],
                // logout users
                $logoutListener,
                // authentication via a simple form composed of a username and a password
                new UsernamePasswordFormAuthenticationListener(
                    $c['securityContext'],
                    $c['authentificationManager'],
                    new SessionAuthenticationStrategy(SessionAuthenticationStrategy::INVALIDATE),
                    $c['httpUtils'],
                    Kernel::SECURITY_DOMAIN,
                    new AuthenticationSuccessHandler($c['httpUtils'], array(
                        'always_use_default_target_path' => false,
                        'default_target_path'            => '/rz-admin',
                        'login_path'                     => '/login',
                        'target_path_parameter'          => '_target_path',
                        'use_referer'                    => true,
                    )),
                    new DefaultAuthenticationFailureHandler($c['httpKernel'], $c['httpUtils'], array(
                        'failure_path'           => '/login',
                        'failure_forward'        => false,
                        'login_path'             => '/login',
                        'failure_path_parameter' => '_failure_path'
                    ), $c['logger']),
                    array(
                        'check_path' => '/rz-admin/login_check',
                    ),
                    $c['logger'], // A LoggerInterface instance
                    $c['dispatcher'],
                    null//$c['csrfProvider'] //csrfTokenManager
                ),
                // enforces access control rules
                new AccessListener(
                    $c['securityContext'],
                    $c['accessDecisionManager'],
                    $accessMap,
                    $c['authentificationManager']
                ),
                $c["switchUser"],
            );

            $map->add($requestMatcher, $listeners, $c['firewallExceptionListener']);

            return $map;
        });
    }
}
