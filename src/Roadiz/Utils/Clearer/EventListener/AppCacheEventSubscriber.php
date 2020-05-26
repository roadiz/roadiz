<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Clearer\EventListener;

use RZ\Roadiz\Core\Events\Cache\CachePurgeRequestEvent;
use RZ\Roadiz\Utils\Clearer\AppCacheClearer;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AppCacheEventSubscriber implements EventSubscriberInterface
{
    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
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
        try {
            $clearer = new AppCacheClearer($event->getKernel()->getCacheDir());
            $clearer->clear();
            $event->addMessage($clearer->getOutput(), static::class, 'httpAppCache');
        } catch (\Exception $e) {
            $event->addError($e->getMessage(), static::class, 'httpAppCache');
        }
    }
}
