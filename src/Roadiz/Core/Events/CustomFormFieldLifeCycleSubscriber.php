<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Events;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Pimple\Container;
use RZ\Roadiz\Core\Entities\CustomFormField;
use RZ\Roadiz\Core\Handlers\CustomFormFieldHandler;

class CustomFormFieldLifeCycleSubscriber implements EventSubscriber
{
    /**
     * @var Container
     */
    private $container;

    /**
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
        $field = $event->getEntity();
        if ($field instanceof CustomFormField) {
            /*
             * Automatically set position only if not manually set before.
             */
            if ($field->getPosition() === 0.0) {
                /*
                 * Get the last index after last node in parent
                 */
                /** @var CustomFormFieldHandler $customFormFieldHandler */
                $customFormFieldHandler = $this->container->offsetGet('custom_form_field.handler');
                $customFormFieldHandler->setCustomFormField($field);
                $lastPosition = $customFormFieldHandler->cleanPositions(false);
                if ($lastPosition > 1) {
                    /*
                     * Need to decrement position because current field is already
                     * in custom-form field collection count.
                     */
                    $field->setPosition($lastPosition - 1);
                } else {
                    $field->setPosition($lastPosition);
                }
            }
        }
    }
}
