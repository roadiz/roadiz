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
 * @file MaintenanceModeSubscriber.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Events;

use Pimple\Container;
use RZ\Roadiz\CMS\Controllers\AppController;
use RZ\Roadiz\Core\ContainerAwareInterface;
use RZ\Roadiz\Core\Entities\Theme;
use RZ\Roadiz\Core\Exceptions\MaintenanceModeException;
use RZ\Roadiz\Core\HttpFoundation\Request as RoadizRequest;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Utils\Theme\ThemeResolverInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class MaintenanceModeSubscriber
 * @package RZ\Roadiz\Core\Events
 */
class MaintenanceModeSubscriber implements EventSubscriberInterface
{
    protected $container;

    /**
     * @return array
     */
    protected function getAuthorizedRoutes()
    {
        return [
            'loginPage',
            'loginRequestPage',
            'loginRequestConfirmPage',
            'loginResetConfirmPage',
            'loginResetPage',
            'loginFailedPage',
            'loginCheckPage',
            'logoutPage',
            'FontFile',
            'FontFaceCSS',
            'loginImagePage',
            'interventionRequestProcess',
        ];
    }

    /**
     * MaintenanceModeSubscriber constructor.
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['onRequest', 31], // Should be lower than RouterListener (32) to be executed after!
        ];
    }

    /**
     * @param RequestEvent $event
     * @throws MaintenanceModeException
     */
    public function onRequest(RequestEvent $event)
    {
        if ($event->isMasterRequest()) {
            if (!in_array($event->getRequest()->get('_route'), $this->getAuthorizedRoutes()) &&
                (boolean) $this->container['settingsBag']->get('maintenance_mode') === true) {
                if (!$this->container['securityAuthorizationChecker']->isGranted('ROLE_BACKEND_USER')) {
                    /** @var ThemeResolverInterface $themeResolver */
                    $themeResolver = $this->container['themeResolver'];
                    /** @var Theme $theme */
                    $theme = $themeResolver->findTheme(null);
                    if (null !== $theme) {
                        throw new MaintenanceModeException($this->getControllerForTheme($theme, $event->getRequest()));
                    }
                    throw new MaintenanceModeException();
                }
            }
        }
    }

    /**
     * @param Theme   $theme
     * @param Request $request
     *
     * @return AppController
     */
    private function getControllerForTheme(Theme $theme, Request $request)
    {
        /** @var Kernel $kernel */
        $kernel = $this->container['kernel'];
        $ctrlClass = $theme->getClassName();
        $controller = new $ctrlClass();

        /*
         * Inject current Kernel to the matched Controller
         */
        if ($controller instanceof ContainerAwareInterface) {
            $controller->setContainer($kernel->getContainer());
        }
        /*
         * Do not inject current theme when
         * Install mode is active.
         */
        if (true !== $kernel->isInstallMode() &&
            $request instanceof RoadizRequest &&
            $controller instanceof AppController) {
            // No node controller matching in install mode
            $request->setTheme($controller->getTheme());
        }

        /*
         * Set request locale if _locale param
         * is present in Route.
         */
        $routeParams = $request->get('_route_params');
        if (!empty($routeParams["_locale"])) {
            $request->setLocale($routeParams["_locale"]);
        }

        /*
         * Prepare base assignation
         */
        if ($controller instanceof AppController) {
            $controller->__init();
        }
        return $controller;
    }
}
