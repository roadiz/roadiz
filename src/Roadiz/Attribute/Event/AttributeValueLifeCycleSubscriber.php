<?php
declare(strict_types=1);

namespace RZ\Roadiz\Attribute\Event;

use ArrayIterator;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use RZ\Roadiz\Attribute\Model\AttributeValueInterface;
use RZ\Roadiz\Core\Entities\AttributeValue;

class AttributeValueLifeCycleSubscriber implements EventSubscriber
{
    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return [
            Events::prePersist,
            Events::onFlush,
        ];
    }

    /**
     * @param LifecycleEventArgs $event
     */
    public function prePersist(LifecycleEventArgs $event)
    {
        $entity = $event->getEntity();
        if ($entity instanceof AttributeValueInterface) {
            /*
             * Automatically set position only if not manually set before.
             */
            if ($entity->getPosition() === 0.0) {
                /*
                 * Get the last index after last node in parent
                 */
                $nodeAttributes = $entity->getAttributable()->getAttributeValues();
                $lastPosition = 1;
                foreach ($nodeAttributes as $nodeAttribute) {
                    $nodeAttribute->setPosition($lastPosition);
                    $lastPosition++;
                }

                $entity->setPosition($lastPosition);
            }
        }
    }

    /**
     * @param OnFlushEventArgs $eventArgs
     *
     * @throws \Exception
     */
    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        $em = $eventArgs->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof AttributeValueInterface) {
                $classMetadata = $em->getClassMetadata(AttributeValue::class);
                foreach ($uow->getEntityChangeSet($entity) as $keyField => $field) {
                    if ($keyField === 'position') {
                        $nodeAttributes = $entity->getAttributable()->getAttributeValues();
                        /*
                         * Need to resort collection based on updated position.
                         */
                        $iterator = $nodeAttributes->getIterator();
                        if ($iterator instanceof ArrayIterator) {
                            // define ordering closure, using preferred comparison method/field
                            $iterator->uasort(function (AttributeValueInterface $first, AttributeValueInterface $second) {
                                return $first->getPosition() > $second->getPosition() ? 1 : -1;
                            });
                        }

                        $lastPosition = 1;
                        /** @var AttributeValueInterface $nodeAttribute */
                        foreach ($iterator as $nodeAttribute) {
                            $nodeAttribute->setPosition($lastPosition);
                            $uow->computeChangeSet($classMetadata, $nodeAttribute);
                            $lastPosition++;
                        }
                    }
                }
            }
        }
    }
}
