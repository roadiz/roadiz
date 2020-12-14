<?php
declare(strict_types=1);

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
