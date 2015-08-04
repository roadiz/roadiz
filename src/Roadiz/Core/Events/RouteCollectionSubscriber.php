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
 * @file RouteCollectionSubscriber.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Events;

use RZ\Roadiz\Core\Routing\RouteDumper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Events for route collection generations.
 */
class RouteCollectionSubscriber implements EventSubscriberInterface
{
    protected $routeDumper;
    protected $stopwatch;

    public function __construct(RouteCollection $routeCollection, Stopwatch $stopwatch)
    {
        $this->stopwatch = $stopwatch;
        $this->routeDumper = new RouteDumper(
            $routeCollection,
            ROADIZ_ROOT . '/gen-src/Compiled'
        );
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        // https://github.com/symfony/HttpKernel/blob/master/EventListener/RouterListener.php
        // Use 33 priority
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 33],
        ];
    }

    public static function needToDumpUrlTools()
    {
        $fs = new Filesystem();
        return (!$fs->exists(ROADIZ_ROOT . '/gen-src/Compiled/GlobalUrlMatcher.php') ||
            !$fs->exists(ROADIZ_ROOT . '/gen-src/Compiled/GlobalUrlGenerator.php'));
    }

    /**
     *
     */
    public function onKernelRequest()
    {
        $this->stopwatch->start('dumpUrlUtils');
        $this->routeDumper->dump();
        $this->stopwatch->stop('dumpUrlUtils');
    }
}
