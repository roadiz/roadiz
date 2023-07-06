<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Clearer\EventListener;

use RZ\Roadiz\Core\Events\Cache\CachePurgeRequestEvent;
use RZ\Roadiz\Utils\Clearer\TemplatesCacheClearer;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TemplatesCacheEventSubscriber implements EventSubscriberInterface
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
            $clearer = new TemplatesCacheClearer($event->getKernel()->getCacheDir());
            $clearer->clear();
            $event->addMessage($clearer->getOutput(), static::class, 'Templates cache');
        } catch (\Exception $e) {
            $event->addError($e->getMessage(), static::class, 'Templates cache');
        }
    }
}
