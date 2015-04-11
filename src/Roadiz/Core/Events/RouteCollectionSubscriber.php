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

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Routing\Generator\Dumper\PhpGeneratorDumper;
use Symfony\Component\Routing\Matcher\Dumper\PhpMatcherDumper;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Events for route collection generations.
 */
class RouteCollectionSubscriber implements EventSubscriberInterface
{
    protected $routeCollection;
    protected $stopwatch;
    protected $fs;

    public function __construct(RouteCollection $routeCollection, Stopwatch $stopwatch)
    {
        $this->routeCollection = $routeCollection;
        $this->stopwatch = $stopwatch;
        $this->fs = new Filesystem();
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }

    /**
     *
     */
    public function onKernelRequest()
    {
        $this->stopwatch->start('dumpUrlUtils');

        if (!$this->fs->exists(ROADIZ_ROOT . '/gen-src/Compiled/GlobalUrlMatcher.php') ||
            !$this->fs->exists(ROADIZ_ROOT . '/gen-src/Compiled/GlobalUrlGenerator.php')) {
            if (!$this->fs->exists(ROADIZ_ROOT . '/gen-src/Compiled')) {
                $this->fs->mkdir(ROADIZ_ROOT . '/gen-src/Compiled', 0755);
            }
            /*
             * Generate custom UrlMatcher
             */
            $dumper = new PhpMatcherDumper($this->routeCollection);
            $class = $dumper->dump([
                'class' => 'GlobalUrlMatcher',
            ]);
            $this->fs->dumpFile(ROADIZ_ROOT . '/gen-src/Compiled/GlobalUrlMatcher.php', $class);

            /*
             * Generate custom UrlGenerator
             */
            $dumper = new PhpGeneratorDumper($this->routeCollection);
            $class = $dumper->dump([
                'class' => 'GlobalUrlGenerator',
            ]);
            $this->fs->dumpFile(ROADIZ_ROOT . '/gen-src/Compiled/GlobalUrlGenerator.php', $class);
        }

        $this->stopwatch->stop('dumpUrlUtils');
    }
}
