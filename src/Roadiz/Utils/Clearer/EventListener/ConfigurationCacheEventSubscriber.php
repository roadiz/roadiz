<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Clearer\EventListener;

use RZ\Roadiz\Core\Events\Cache\CachePurgeRequestEvent;
use RZ\Roadiz\Utils\Clearer\ConfigurationCacheClearer;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConfigurationCacheEventSubscriber implements EventSubscriberInterface
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
        try {
            $clearer = new ConfigurationCacheClearer($event->getKernel()->getCacheDir());
            $clearer->clear();
            $event->addMessage($clearer->getOutput(), static::class, 'Configuration cache');
        } catch (\Exception $e) {
            $event->addError($e->getMessage(), static::class, 'Configuration cache');
        }
    }
}
