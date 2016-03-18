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

use Pimple\Container;
use RZ\Roadiz\Core\Entities\Role;
use RZ\Roadiz\Utils\Security\FirewallEntry;

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
     * @param Container $container
     */
    public static function setupDependencyInjection(Container $container)
    {
        parent::setupDependencyInjection($container);

        $firewallBasePattern = '^/rz-admin';
        $firewallBasePath = '/rz-admin';
        $firewallLogin = '/login';
        $firewallLogout = $firewallBasePath . '/logout';
        $firewallLoginCheck = $firewallBasePath . '/login_check';
        $firewallBaseRole = Role::ROLE_BACKEND_USER;

        $firewallEntry = new FirewallEntry(
            $container,
            $firewallBasePattern,
            $firewallBasePath,
            $firewallLogin,
            $firewallLogout,
            $firewallLoginCheck,
            $firewallBaseRole
        );
        $firewallEntry->withSwitchUserListener();

        $container['firewallMap']->add(
            $firewallEntry->getRequestMatcher(),
            $firewallEntry->getListeners(),
            $container['firewallExceptionListener']
        );
    }
}
