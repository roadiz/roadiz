<?php
/**
 * Copyright (c) 2017. Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
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
 * @file DebugBarSubscriber.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace RZ\Roadiz\Core\Events;

use Pimple\Container;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Stopwatch\Stopwatch;

class DebugBarSubscriber implements EventSubscriberInterface
{
    protected $container = null;

    /**
     * DebugPanel constructor.
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
            KernelEvents::RESPONSE => ['onKernelResponse', -128],
            KernelEvents::REQUEST => 'onKernelRequest',
            KernelEvents::CONTROLLER => 'onControllerMatched',
        ];
    }

    /**
     * @param FilterResponseEvent $event
     *
     * @return bool
     */
    protected function supports(FilterResponseEvent $event)
    {
        $response = $event->getResponse();
        if ($event->isMasterRequest() &&
            $this->container['settingsBag']->get('display_debug_panel') == true &&
            false !== strpos($response->headers->get('Content-Type'), 'text/html')) {
            return true;
        }

        return false;
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        if ($this->supports($event)) {
            /** @var Stopwatch $stopWatch */
            $stopWatch = $this->container['stopwatch'];
            $response = $event->getResponse();

            if ($stopWatch->isStarted('controllerHandling')) {
                $stopWatch->stop('controllerHandling');
            }
            if ($stopWatch->isStarted('twigRender')) {
                $stopWatch->stop('twigRender');
            }

            if ($stopWatch->isStarted('__section__')) {
                $stopWatch->stopSection('runtime');
            }


            if (false !== strpos($response->getContent(), '</body>') &&
                false !== strpos($response->getContent(), '</head>')) {
                $content = str_replace(
                    '</head>',
                    $this->container['debugbar.renderer']->renderHead() . "</head>",
                    $response->getContent()
                );
                $content = str_replace(
                    '</body>',
                    $this->container['debugbar.renderer']->render() . "</body>",
                    $content
                );
                $response->setContent($content);
                $event->setResponse($response);
            }
        }
    }

    /**
     * Start a stopwatch event when a kernel start handling.
     */
    public function onKernelRequest()
    {
        $this->container['stopwatch']->start('requestHandling');
        $this->container['stopwatch']->start('matchingRoute');
    }
    /**
     * Stop request-handling stopwatch event and
     * start a new stopwatch event when a controller is instanciated.
     */
    public function onControllerMatched()
    {
        $this->container['stopwatch']->stop('matchingRoute');
        $this->container['stopwatch']->stop('requestHandling');
        $this->container['stopwatch']->start('controllerHandling');
    }
}
