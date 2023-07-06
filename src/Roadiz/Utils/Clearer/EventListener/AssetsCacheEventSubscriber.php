<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Clearer\EventListener;

use RZ\Roadiz\Core\Events\Cache\CachePurgeAssetsRequestEvent;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Utils\Clearer\AssetsClearer;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AssetsCacheEventSubscriber implements EventSubscriberInterface
{
    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
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
        $kernel = $event->getKernel();
        if (!$kernel instanceof Kernel) {
            return;
        }
        try {
            $clearer = new AssetsClearer($kernel->getPublicCachePath());
            $clearer->clear();
            $event->addMessage($clearer->getOutput(), static::class, 'Assets cache');
        } catch (\Exception $e) {
            $event->addError($e->getMessage(), static::class, 'Assets cache');
        }
    }
}
