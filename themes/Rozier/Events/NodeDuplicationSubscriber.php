<?php
declare(strict_types=1);

namespace Themes\Rozier\Events;

use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\Core\Events\Node\NodeDuplicatedEvent;
use RZ\Roadiz\Core\Handlers\HandlerFactoryInterface;
use RZ\Roadiz\Core\Handlers\NodeHandler;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @package Themes\Rozier\Events
 */
class NodeDuplicationSubscriber implements EventSubscriberInterface
{
    protected HandlerFactoryInterface $handlerFactory;
    private EntityManagerInterface $entityManager;

    /**
     * @param EntityManagerInterface  $entityManager
     * @param HandlerFactoryInterface $handlerFactory
     */
    public function __construct(EntityManagerInterface $entityManager, HandlerFactoryInterface $handlerFactory)
    {
        $this->entityManager = $entityManager;
        $this->handlerFactory = $handlerFactory;
    }

    public static function getSubscribedEvents()
    {
        return [
            NodeDuplicatedEvent::class => 'cleanPosition',
        ];
    }

    /**
     * @param NodeDuplicatedEvent $event
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function cleanPosition(NodeDuplicatedEvent $event)
    {
        /** @var NodeHandler $nodeHandler */
        $nodeHandler = $this->handlerFactory->getHandler($event->getNode());
        $nodeHandler->setNode($event->getNode());
        $nodeHandler->cleanChildrenPositions();
        $nodeHandler->cleanPositions();

        $this->entityManager->flush();
    }
}
