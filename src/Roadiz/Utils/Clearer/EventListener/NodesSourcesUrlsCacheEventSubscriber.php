<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Clearer\EventListener;

use RZ\Roadiz\Core\Events\Cache\CachePurgeRequestEvent;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Utils\Clearer\NodesSourcesUrlsCacheClearer;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class NodesSourcesUrlsCacheEventSubscriber implements EventSubscriberInterface
{
    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            CachePurgeRequestEvent::class => ['onPurgeRequest', 3],
        ];
    }

    /**
     * @param CachePurgeRequestEvent $event
     */
    public function onPurgeRequest(CachePurgeRequestEvent $event)
    {
        $kernel = $event->getKernel();
        if (!$kernel instanceof Kernel) {
            return;
        }
        try {
            $clearer = new NodesSourcesUrlsCacheClearer(
                $kernel->get('nodesSourcesUrlCacheProvider')
            );
            if (false !== $clearer->clear()) {
                $event->addMessage($clearer->getOutput(), static::class, 'NodesSources URL cache');
            }
        } catch (\Exception $e) {
            $event->addError($e->getMessage(), static::class, 'NodesSources URL cache');
        }
    }
}
