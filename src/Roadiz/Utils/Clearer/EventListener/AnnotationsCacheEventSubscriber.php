<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Clearer\EventListener;

use RZ\Roadiz\Core\Events\Cache\CachePurgeRequestEvent;
use RZ\Roadiz\Utils\Clearer\AnnotationsCacheClearer;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AnnotationsCacheEventSubscriber implements EventSubscriberInterface
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
            $clearer = new AnnotationsCacheClearer($event->getKernel()->getCacheDir());
            if (false !== $clearer->clear()) {
                $event->addMessage(
                    $clearer->getOutput(),
                    static::class,
                    'PHP annotations cache'
                );
            }
        } catch (\Exception $e) {
            $event->addError(
                $e->getMessage(),
                static::class,
                'PHP annotations cache'
            );
        }
    }
}
