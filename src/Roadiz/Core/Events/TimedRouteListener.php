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
 * @file TimedRouteListener.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Events;

use Psr\Log\LoggerInterface;
use Symfony\Cmf\Component\Routing\ChainRouter;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * TimedRouteListener.
 */
class TimedRouteListener extends RouterListener
{
    protected $stopwatch;

    /**
     * TimedRouteListener constructor.
     * @param ChainRouter $router
     * @param RequestContext|null $context
     * @param LoggerInterface|null $logger
     * @param RequestStack|null $requestStack
     * @param Stopwatch|null $stopwatch
     */
    public function __construct(
        ChainRouter $router,
        RequestStack $requestStack = null,
        RequestContext $context = null,
        LoggerInterface $logger = null,
        Stopwatch $stopwatch = null
    ) {
        parent::__construct($router, $requestStack, $context, $logger);

        $this->stopwatch = $stopwatch;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $this->stopwatch->start('routeListener');
        parent::onKernelRequest($event);
        $this->stopwatch->stop('routeListener');
    }
}
