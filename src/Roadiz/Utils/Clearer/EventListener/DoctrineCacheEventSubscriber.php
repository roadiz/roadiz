<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Clearer\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use RZ\Roadiz\Core\Events\Cache\CachePurgeRequestEvent;
use RZ\Roadiz\Core\Kernel;
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
        $kernel = $event->getKernel();
        if (!$kernel instanceof Kernel) {
            return;
        }
        try {
            $clearer = new DoctrineCacheClearer(
                $kernel->get(ManagerRegistry::class),
                $kernel
            );
            $clearer->clear();
            $event->addMessage($clearer->getOutput(), static::class, 'Doctrine cache');
        } catch (\Exception $e) {
            $event->addError($e->getMessage(), static::class, 'Doctrine cache');
        }
    }
}
