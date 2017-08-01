<?php
/**
 * Copyright (c) 2017. Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
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
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file LeafEntityLifeCycleSubscriber.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace RZ\Roadiz\Core\Events;


use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Pimple\Container;
use RZ\Roadiz\Core\AbstractEntities\LeafInterface;
use RZ\Roadiz\Core\Handlers\AbstractHandler;

class LeafEntityLifeCycleSubscriber implements EventSubscriber
{
    /**
     * @var Container
     */
    private $container;

    /**
     * UserLifeCycleSubscriber constructor.
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return [
            Events::prePersist,
        ];
    }

    /**
     * @param LifecycleEventArgs $event
     */
    public function prePersist(LifecycleEventArgs $event)
    {
        $entity = $event->getEntity();
        if ($entity instanceof LeafInterface) {
            /*
             * Automatically set position only if not manually set before.
             */
            try {
                /** @var AbstractHandler $handler */
                $handler = $this->container->offsetGet('factory.handler')->getHandler($entity);
                if (method_exists($handler, 'cleanPositions')) {
                    if ($entity->getPosition() === 0.0) {
                        /*
                         * Get the last index after last tag in parent
                         */
                        $lastPosition = $handler->cleanPositions(false);
                        if ($lastPosition > 1 && null !== $entity->getParent()) {
                            /*
                             * Need to decrement position because current tag is already
                             * in parent's children collection count.
                             */
                            $entity->setPosition($lastPosition - 1);
                        } else {
                            $entity->setPosition($lastPosition);
                        }
                    } elseif ($entity->getPosition() === 0.5) {
                        /*
                         * Position is set to 0.5 so we need to
                         * shift all tags to the bottom.
                         */
                        $handler->cleanPositions(true);
                    }
                }
            } catch (\InvalidArgumentException $e) {

            }
        }
    }
}