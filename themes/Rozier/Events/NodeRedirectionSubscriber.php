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
 * @file NodesSourcesUrlSubscriber.php
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Events;

use RZ\Roadiz\Core\Events\Cache\CachePurgeRequestEvent;
use RZ\Roadiz\Core\Events\FilterNodePathEvent;
use RZ\Roadiz\Core\Events\Node\NodePathChangedEvent;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Utils\Node\NodeMover;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribe to Node, NodesSources and UrlAlias event to clear ns url cache.
 */
class NodeRedirectionSubscriber implements EventSubscriberInterface
{
    /**
     * @var NodeMover
     */
    protected $nodeMover;
    /**
     * @var Kernel
     */
    protected $kernel;

    /**
     * NodeRedirectionSubscriber constructor.
     *
     * @param NodeMover $nodeMover
     * @param Kernel    $kernel
     */
    public function __construct(NodeMover $nodeMover, Kernel $kernel)
    {
        $this->nodeMover = $nodeMover;
        $this->kernel = $kernel;
    }

    public static function getSubscribedEvents()
    {
        return [
            NodePathChangedEvent::class => 'redirectOldPaths'
        ];
    }

    /**
     * Empty nodeSources Url cache
     *
     * @param FilterNodePathEvent      $event
     * @param string                   $eventName
     * @param EventDispatcherInterface $dispatcher
     */
    public function redirectOldPaths(FilterNodePathEvent $event, $eventName, EventDispatcherInterface $dispatcher)
    {
        if (null !== $event->getNode() &&
            $event->getNode()->isPublished() &&
            $event->getNode()->getNodeType()->isReachable() &&
            count($event->getPaths()) > 0) {
            $this->nodeMover->redirectAll($event->getNode(), $event->getPaths());

            $dispatcher->dispatch(new CachePurgeRequestEvent($this->kernel));
        }
    }
}
