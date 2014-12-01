<?php
/**
 * Copyright © 2014, REZO ZERO
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
 * Except as contained in this notice, the name of the REZO ZERO shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from the REZO ZERO SARL.
 *
 * @file RoutingServiceProvider.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Services;

use Pimple\Container;
use Symfony\Component\Routing\RouteCollection;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\Entities\Theme;

/**
 * Register routing services for dependency injection container.
 */
class RoutingServiceProvider implements \Pimple\ServiceProviderInterface
{
    public function register(Container $container)
    {
        if (isset($container['config']['install']) &&
            true === $container['config']['install']) {
            /*
             * Get Install routes
             */
            $container['routeCollection'] = function ($c) {

                $installClassname = Kernel::INSTALL_CLASSNAME;
                $feCollection = $installClassname::getRoutes();
                $rCollection = new RouteCollection();
                $rCollection->addCollection($feCollection);

                return $rCollection;
            };
        } else {
            /*
             * Parse existing themes
             */
            $container['backendClass'] = function ($c) {
                $theme = $c['em']->getRepository('RZ\Roadiz\Core\Entities\Theme')
                                 ->findAvailableBackend();

                if ($theme !== null) {
                    return $theme->getClassName();
                }

                return 'RZ\Roadiz\CMS\Controllers\BackendController';
            };

            $container['frontendThemes'] = function ($c) {
                $themes = $c['em']->getRepository('RZ\Roadiz\Core\Entities\Theme')
                                  ->findAvailableFrontends();



                if (count($themes) < 1) {

                    $defaultTheme = new Theme();
                    $defaultTheme->setClassName('RZ\Roadiz\CMS\Controllers\FrontendController');
                    $defaultTheme->setAvailable(true);

                    return array(
                        $defaultTheme
                    );
                } else {
                    return $themes;
                }
            };
            /*
             * Get App routes
             */
            $container['routeCollection'] = function ($c) {

                $c['stopwatch']->start('routeCollection');
                $rCollection = new RouteCollection();

                /*
                 * Add Assets controller routes
                 */
                $rCollection->addCollection(
                    \RZ\Roadiz\CMS\Controllers\AssetsController::getRoutes()
                );

                /*
                 * Add Entry points controller routes
                 */
                $rCollection->addCollection(
                    \RZ\Roadiz\CMS\Controllers\EntryPointsController::getRoutes()
                );

                /*
                 * Add Backend routes
                 */
                $beClass = $c['backendClass'];
                $cmsCollection = $beClass::getRoutes();
                if ($cmsCollection !== null) {
                    $rCollection->addCollection(
                        $cmsCollection,
                        '/rz-admin',
                        array('_scheme' => 'https')
                    );
                }

                /*
                 * Add Frontend routes
                 *
                 * return 'RZ\Roadiz\CMS\Controllers\FrontendController';
                 */
                foreach ($c['frontendThemes'] as $theme) {
                    $feClass = $theme->getClassName();
                    $feCollection = $feClass::getRoutes();
                    if ($feCollection !== null) {

                        // set host pattern if defined
                        if ($theme->getHostname() != '*' &&
                            $theme->getHostname() != '') {

                            $feCollection->setHost($theme->getHostname());
                        }
                        $rCollection->addCollection($feCollection);
                    }
                }
                $c['stopwatch']->stop('routeCollection');
                return $rCollection;
            };
        }
    }
}
