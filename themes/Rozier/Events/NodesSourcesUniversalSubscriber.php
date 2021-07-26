<?php
declare(strict_types=1);

namespace Themes\Rozier\Events;

use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\Core\Events\NodesSources\NodesSourcesUpdatedEvent;
use RZ\Roadiz\Utils\Node\UniversalDataDuplicator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @package Themes\Rozier\Events
 */
class NodesSourcesUniversalSubscriber implements EventSubscriberInterface
{
    private EntityManagerInterface $em;
    private UniversalDataDuplicator $universalDataDuplicator;

    /**
     * @param EntityManagerInterface $em
     * @param UniversalDataDuplicator $universalDataDuplicator
     */
    public function __construct(EntityManagerInterface $em, UniversalDataDuplicator $universalDataDuplicator)
    {
        $this->em = $em;
        $this->universalDataDuplicator = $universalDataDuplicator;
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
            $this->em->flush();
        }
    }
}
