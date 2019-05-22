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
 * @file RoadizDebugBar.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace RZ\Roadiz\Utils\DebugBar;

use DebugBar\Bridge\DoctrineCollector;
use DebugBar\DataCollector\ConfigCollector;
use DebugBar\DataCollector\MemoryCollector;
use DebugBar\DebugBar;
use Pimple\Container;
use RZ\Roadiz\Utils\DebugBar\DataCollector\AccessMapCollector;
use RZ\Roadiz\Utils\DebugBar\DataCollector\AuthCollector;
use RZ\Roadiz\Utils\DebugBar\DataCollector\DispatcherCollector;
use RZ\Roadiz\Utils\DebugBar\DataCollector\ThemesCollector;
use RZ\Roadiz\Utils\DebugBar\DataCollector\VersionsCollector;

class RoadizDebugBar extends DebugBar
{
    /**
     * @var Container
     */
    private $container;

    /**
     * RoadizDebugBar constructor.
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->addCollector($container['messagescollector']);
        $this->addCollector(new ThemesCollector($container['themeResolver'], $container['requestStack']));
        $this->addCollector(new VersionsCollector());
        $this->addCollector(new StopwatchDataCollector($container['stopwatch'], $container['twig.profile']));
        $this->addCollector(new MemoryCollector());
        $this->addCollector(new DoctrineCollector($container['doctrine.debugstack']));
        $this->addCollector(new ConfigCollector($container['config']));
        $this->addCollector(new AuthCollector($container['securityTokenStorage']));
        $this->addCollector(new DispatcherCollector($container['dispatcher']));
        $this->addCollector(new AccessMapCollector($container['accessMap'], $container['requestStack']));
    }
}
