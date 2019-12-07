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
 * @file DispatcherCollector.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace RZ\Roadiz\Utils\DebugBar\DataCollector;

use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use Symfony\Component\EventDispatcher\Debug\WrappedListener;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DispatcherCollector extends DataCollector implements Renderable
{
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;


    /**
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @{inheritDoc}
     */
    public function collect()
    {
        $array = [
            'listeners' => [],
        ];
        foreach ($this->dispatcher->getListeners() as $eventName => $listeners) {
            /** @var EventSubscriberInterface $listener */
            foreach ($listeners as $priority => $listener) {
                if ($listener instanceof WrappedListener) {
                    $listener = $listener->getWrappedListener();
                }

                if (is_object($listener)) {
                    $className = get_class($listener);
                    $method = null;
                } else {
                    if (is_object($listener[0])) {
                        $className = get_class($listener[0]);
                    } else {
                        $className = '';
                    }

                    $method = $listener[1];
                }
                $array['listeners'][$eventName . '('.$priority.')'] = $className . ':' . $method;
            }
        }

        return $array;
    }

    /**
     * @{inheritDoc}
     */
    public function getName()
    {
        return 'dispatcher';
    }

    /**
     * @{inheritDoc}
     */
    public function getWidgets()
    {
        $widgets = [
            'dispatcher' => [
                'icon' => 'flag',
                'widget' => 'PhpDebugBar.Widgets.KVListWidget',
                'map' => 'dispatcher.listeners',
                'default' => '{}'
            ]
        ];

        return $widgets;
    }
}
