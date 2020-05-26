<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Clearer\EventListener;

use RZ\Roadiz\Core\Events\Cache\CachePurgeAssetsRequestEvent;
use RZ\Roadiz\Utils\Clearer\AssetsClearer;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AssetsCacheEventSubscriber implements EventSubscriberInterface
{
    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            CachePurgeAssetsRequestEvent::class => ['onPurgeAssetsRequest', 0],
        ];
    }

    /**
     * @param CachePurgeAssetsRequestEvent $event
     */
    public function onPurgeAssetsRequest(CachePurgeAssetsRequestEvent $event)
    {
        try {
            $clearer = new AssetsClearer($event->getKernel()->getPublicCachePath());
            $clearer->clear();
            $event->addMessage($clearer->getOutput(), static::class, 'assetsCache');
        } catch (\Exception $e) {
            $event->addError($e->getMessage(), static::class, 'assetsCache');
        }
    }
}
