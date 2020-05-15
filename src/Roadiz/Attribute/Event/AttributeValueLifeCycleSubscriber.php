<?php
/**
 * Copyright © 2019, Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the roadiz shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file AttributeValueLifeCycleSubscriber.php
 * @author Ambroise Maupate
 *
 */
namespace RZ\Roadiz\Attribute\Event;

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
                        // define ordering closure, using preferred comparison method/field
                        $iterator->uasort(function (AttributeValueInterface $first, AttributeValueInterface $second) {
                            return $first->getPosition() > $second->getPosition() ? 1 : -1;
                        });

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
