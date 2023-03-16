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
 * @file ControllerMatchedSubscriber.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Events;

use RZ\Roadiz\CMS\Controllers\AppController;
use RZ\Roadiz\Core\ContainerAwareInterface;
use RZ\Roadiz\Core\Entities\Theme;
use RZ\Roadiz\Core\HttpFoundation\Request as RoadizRequest;
use RZ\Roadiz\Core\Kernel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Event dispatched after a route has been matched.
 */
class ControllerMatchedSubscriber implements EventSubscriberInterface
{
    private $kernel;
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
     * @param \Symfony\Component\HttpKernel\Event\FilterControllerEvent $event
     */
    public function onControllerMatched(FilterControllerEvent $event)
    {
        if (null !== $this->stopwatch) {
            $this->stopwatch->start('onControllerMatched');
        }
        $matchedCtrl = $event->getController()[0];

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
