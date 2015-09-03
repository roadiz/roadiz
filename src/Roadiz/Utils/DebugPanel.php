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
 * @file DebugPanel.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Utils;

use Pimple\Container;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event subscriber which append a debug console after any HTML output.
 */
class DebugPanel implements EventSubscriberInterface
{
    protected $container = null;

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
            KernelEvents::REQUEST => 'onKernelRequest',
            KernelEvents::RESPONSE => 'onKernelResponse',
            KernelEvents::CONTROLLER => 'onControllerMatched',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        if ($this->container['stopwatch']->isStarted('controllerHandling')) {
            $this->container['stopwatch']->stop('controllerHandling');
        }

        if ($this->container['stopwatch']->isStarted('twigRender')) {
            $this->container['stopwatch']->stop('twigRender');
        }

        $response = $event->getResponse();

        if (false !== strpos($response->getContent(), '<!-- ##debug_panel## -->')) {
            $content = str_replace('<!-- ##debug_panel## -->', $this->getDebugView(), $response->getContent());
            $response->setContent($content);
            $event->setResponse($response);
        } elseif (false !== strpos($response->getContent(), '</body>')) {
            $content = str_replace('</body>', $this->getDebugView() . "</body>", $response->getContent());
            $response->setContent($content);
            $event->setResponse($response);
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

    private function getDebugView()
    {
        $this->container['stopwatch']->stopSection('runtime');

        $assignation = [
            'stopwatch' => $this->container['stopwatch'],
        ];

        return $this->container['twig.environment']->render('debug-panel.html.twig', $assignation);
    }
}
