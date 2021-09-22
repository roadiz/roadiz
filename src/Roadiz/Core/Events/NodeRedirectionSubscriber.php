<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Events;

use RZ\Roadiz\Core\Events\Cache\CachePurgeRequestEvent;
use RZ\Roadiz\Core\Events\Node\NodePathChangedEvent;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Preview\PreviewResolverInterface;
use RZ\Roadiz\Utils\Node\NodeMover;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribe to Node, NodesSources and UrlAlias event to clear ns url cache.
 */
class NodeRedirectionSubscriber implements EventSubscriberInterface
{
    protected NodeMover $nodeMover;
    protected Kernel $kernel;
    protected PreviewResolverInterface $previewResolver;

    /**
     * @param NodeMover $nodeMover
     * @param Kernel $kernel
     * @param PreviewResolverInterface $previewResolver
     */
    public function __construct(NodeMover $nodeMover, Kernel $kernel, PreviewResolverInterface $previewResolver)
    {
        $this->nodeMover = $nodeMover;
        $this->kernel = $kernel;
        $this->previewResolver = $previewResolver;
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
     * @param NodePathChangedEvent     $event
     * @param string                   $eventName
     * @param EventDispatcherInterface $dispatcher
     */
    public function redirectOldPaths(NodePathChangedEvent $event, $eventName, EventDispatcherInterface $dispatcher)
    {
        if ($this->kernel->isProdMode() &&
            !$this->previewResolver->isPreview() &&
            null !== $event->getNode() &&
            $event->getNode()->isPublished() &&
            $event->getNode()->getNodeType()->isReachable() &&
            count($event->getPaths()) > 0) {
            $this->nodeMover->redirectAll($event->getNode(), $event->getPaths());

            $dispatcher->dispatch(new CachePurgeRequestEvent($this->kernel));
        }
    }
}
