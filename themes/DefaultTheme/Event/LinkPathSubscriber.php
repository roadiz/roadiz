<?php
declare(strict_types=1);

namespace Themes\DefaultTheme\Event;

use GeneratedNodeSources\NSLink;
use RZ\Roadiz\Core\Events\NodesSources\NodesSourcesPathGeneratingEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LinkPathSubscriber implements EventSubscriberInterface
{
    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            /*
             * Needs to execute this BEFORE default nodes-sources path generation
             */
            NodesSourcesPathGeneratingEvent::class => [['onNodesSourcesPath', 0]],
        ];
    }

    /**
     * @param NodesSourcesPathGeneratingEvent $event
     * @param string                   $eventName
     * @param EventDispatcherInterface  $dispatcher
     */
    public function onNodesSourcesPath(
        NodesSourcesPathGeneratingEvent $event,
        $eventName,
        EventDispatcherInterface $dispatcher
    ): void {
        $source = $event->getNodeSource();
        if ($source instanceof NSLink) {
            if (isset($source->getRefNodeSources()[0])) {
                /*
                 * Dispatch path generation AGAIN but
                 * with linked nodeSource.
                 */
                $event->setNodeSource($source->getRefNodeSources()[0]);
                $dispatcher->dispatch($event);
            } else {
                $event->setPath('');
            }
            /*
             * Prevent default nodes-sources path generation
             * to be executed.
             */
            $event->stopPropagation();
        }
    }
}
