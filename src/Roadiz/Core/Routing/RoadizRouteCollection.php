<?php
/**
 * Copyright © 2015, Ambroise Maupate and Julien Blanchet
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
 * @file RoadizRouteCollection.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Routing;

use RZ\Roadiz\CMS\Controllers\AssetsController;
use RZ\Roadiz\CMS\Controllers\EntryPointsController;
use RZ\Roadiz\Core\Entities\Theme;
use Symfony\Component\Routing\RouteCollection;

/**
 *
 */
class RoadizRouteCollection extends RouteCollection
{
    protected $backendClassname;
    protected $frontendThemes;

    /**
     * @param string $backendClassname
     * @param array  $frontendThemes
     * @param string $assetsHost
     */
    public function __construct(
        $backendClassname,
        array $frontendThemes,
        $assetsHost = ''
    ) {
        $this->backendClassname = $backendClassname;
        $this->frontendThemes = $frontendThemes;

        /*
         * Adding Backend routes
         */
        $this->addBackendCollection();

        /*
         * Add Assets controller routes
         */
        $assets = AssetsController::getRoutes();
        if ('' != $assetsHost) {
            $assets->setHost($assetsHost);
        }
        $this->addCollection($assets);

        /*
         * Add Entry points controller routes
         */
        $this->addCollection(EntryPointsController::getRoutes());

        /*
         * Add Frontend routes
         *
         * return 'RZ\Roadiz\CMS\Controllers\FrontendController';
         */
        $this->addThemesCollections();
    }

    protected function addBackendCollection()
    {
        if (class_exists($this->backendClassname)) {
            $class = $this->backendClassname;
            $collection = $class::getRoutes();
            if (null !== $collection) {
                $this->addCollection($collection);
            }
        } else {
            throw new \RuntimeException("Class “" . $this->backendClassname . "” does not exist.", 1);
        }
    }

    protected function addThemesCollections()
    {
        foreach ($this->frontendThemes as $theme) {
            if ($theme instanceof Theme) {
                $feClass = $theme->getClassName();
                $feCollection = $feClass::getRoutes();
                $feBackendCollection = $feClass::getBackendRoutes();

                if ($feCollection !== null) {
                    // set host pattern if defined
                    if ($theme->getHostname() != '*' &&
                        $theme->getHostname() != '') {
                        $feCollection->setHost($theme->getHostname());
                    }
                    /*
                     * Add a global prefix on theme static routes
                     */
                    if ($theme->getRoutePrefix() != '') {
                        $feCollection->addPrefix($theme->getRoutePrefix());
                    }
                    $this->addCollection($feCollection);
                }

                if ($feBackendCollection !== null) {
                    /*
                     * Do not prefix or hostname admin routes.
                     */
                    $this->addCollection($feBackendCollection);
                }
            } else {
                throw new \RuntimeException("Object of type “" . get_class($theme) . "” does not extend RZ\Roadiz\Core\Entities\Theme class.", 1);
            }
        }
    }
}
