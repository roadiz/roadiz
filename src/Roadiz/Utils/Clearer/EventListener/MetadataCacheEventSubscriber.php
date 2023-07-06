<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Clearer\EventListener;

use RZ\Roadiz\Core\Events\Cache\CachePurgeRequestEvent;
use RZ\Roadiz\Utils\Clearer\MetadataCacheClearer;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MetadataCacheEventSubscriber implements EventSubscriberInterface
{
    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            CachePurgeRequestEvent::class => ['onPurgeRequest', 2],
        ];
    }

    /**
     * @param CachePurgeRequestEvent $event
     */
    public function onPurgeRequest(CachePurgeRequestEvent $event)
    {
        try {
            $clearer = new MetadataCacheClearer($event->getKernel()->getCacheDir());
            if (false !== $clearer->clear()) {
                $event->addMessage($clearer->getOutput(), static::class, 'Doctrine metadata cache');
            }
        } catch (\Exception $e) {
            $event->addError($e->getMessage(), static::class, 'Doctrine metadata cache');
        }
    }
}
