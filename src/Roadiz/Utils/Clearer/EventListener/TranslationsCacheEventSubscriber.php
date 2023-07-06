<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Clearer\EventListener;

use RZ\Roadiz\Core\Events\Cache\CachePurgeRequestEvent;
use RZ\Roadiz\Utils\Clearer\TranslationsCacheClearer;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TranslationsCacheEventSubscriber implements EventSubscriberInterface
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
            $clearer = new TranslationsCacheClearer($event->getKernel()->getCacheDir());
            if (false !== $clearer->clear()) {
                $event->addMessage($clearer->getOutput(), static::class, 'Translations cache');
            }
        } catch (\Exception $e) {
            $event->addError($e->getMessage(), static::class, 'Translations cache');
        }
    }
}
