<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Clearer\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use RZ\Roadiz\Core\Events\Cache\CachePurgeRequestEvent;
use RZ\Roadiz\Utils\Clearer\DoctrineCacheClearer;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DoctrineCacheEventSubscriber implements EventSubscriberInterface
{
    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            CachePurgeRequestEvent::class => ['onPurgeRequest', -9999],
        ];
    }

    /**
     * @param CachePurgeRequestEvent $event
     */
    public function onPurgeRequest(CachePurgeRequestEvent $event)
    {
        try {
            $clearer = new DoctrineCacheClearer($event->getKernel()->get(ManagerRegistry::class), $event->getKernel());
            $clearer->clear();
            $event->addMessage($clearer->getOutput(), static::class, 'Doctrine cache');
        } catch (\Exception $e) {
            $event->addError($e->getMessage(), static::class, 'Doctrine cache');
        }
    }
}
