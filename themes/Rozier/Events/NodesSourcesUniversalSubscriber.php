<?php
declare(strict_types=1);

namespace Themes\Rozier\Events;

use Doctrine\Persistence\ManagerRegistry;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Events\NodesSources\NodesSourcesUpdatedEvent;
use RZ\Roadiz\Utils\Node\UniversalDataDuplicator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @package Themes\Rozier\Events
 */
class NodesSourcesUniversalSubscriber implements EventSubscriberInterface
{
    private ManagerRegistry $managerRegistry;
    private UniversalDataDuplicator $universalDataDuplicator;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param UniversalDataDuplicator $universalDataDuplicator
     */
    public function __construct(ManagerRegistry $managerRegistry, UniversalDataDuplicator $universalDataDuplicator)
    {
        $this->universalDataDuplicator = $universalDataDuplicator;
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            NodesSourcesUpdatedEvent::class => 'duplicateUniversalContents',
        ];
    }

    /**
     * @param NodesSourcesUpdatedEvent $event
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function duplicateUniversalContents(NodesSourcesUpdatedEvent $event)
    {
        $source = $event->getNodeSource();

        /*
         * Flush only if duplication happened.
         */
        if (true === $this->universalDataDuplicator->duplicateUniversalContents($source)) {
            $this->managerRegistry->getManagerForClass(NodesSources::class)->flush();
        }
    }
}
