<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Events;

use RZ\Roadiz\CMS\Controllers\AppController;
use RZ\Roadiz\Core\ContainerAwareInterface;
use RZ\Roadiz\Core\Entities\Theme;
use RZ\Roadiz\Core\HttpFoundation\Request as RoadizRequest;
use RZ\Roadiz\Core\Kernel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Event dispatched after a route has been matched.
 */
class ControllerMatchedSubscriber implements EventSubscriberInterface
{
    private $kernel;
    /**
     * @var Stopwatch|null
     */
    private $stopwatch;

    /**
     * @param Kernel $kernel
     * @param Stopwatch $stopwatch
     */
    public function __construct(Kernel $kernel, Stopwatch $stopwatch = null)
    {
        $this->kernel = $kernel;
        $this->stopwatch = $stopwatch;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::CONTROLLER => ['onControllerMatched', 9],
        ];
    }

    /**
     * After a controller has been matched. We need to inject current
     * Kernel instance and main DI container.
     *
     * @param ControllerEvent $event
     */
    public function onControllerMatched(ControllerEvent $event)
    {
        if (null !== $this->stopwatch) {
            $this->stopwatch->start('onControllerMatched');
        }
        $matchedCtrl = $event->getController();
        if (isset($matchedCtrl[0])) {
            $matchedCtrl = $matchedCtrl[0];

            /*
             * Inject current Kernel to the matched Controller
             */
            if ($matchedCtrl instanceof ContainerAwareInterface) {
                $matchedCtrl->setContainer($this->kernel->getContainer());
            }
            /*
             * Do not inject current theme when
             * Install mode is active.
             */
            $request = $event->getRequest();
            $theme = $event->getRequest()->get('theme');

            if ($request instanceof RoadizRequest && true !== $this->kernel->isInstallMode()) {
                if ($matchedCtrl instanceof AppController) {
                    // No node controller matching in install mode
                    $request->setTheme($matchedCtrl->getTheme());
                } elseif ($theme instanceof Theme) {
                    $request->setTheme($theme);
                }
            }

            /*
             * Set request locale if _locale param
             * is present in Route.
             */
            $locale = $event->getRequest()->get('_locale');
            if (\is_string($locale)) {
                $event->getRequest()->setLocale($locale);
            }

            /*
             * Prepare base assignation
             */
            if ($matchedCtrl instanceof AppController) {
                $matchedCtrl->__init();
            }

            if (null !== $this->stopwatch) {
                $this->stopwatch->stop('onControllerMatched');
            }
        }
    }
}
