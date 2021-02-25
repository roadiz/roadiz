<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms\NodeSource;

use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

final class NodeSourceCollectionType extends CollectionType
{
    /**
     * @inheritDoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        /*
         * We need to flatten form data array keys to force numeric array in database
         */
        $builder->addEventListener(FormEvents::SUBMIT, [$this, 'onSubmit'], 40);
    }

    public function onSubmit(FormEvent $event)
    {
        $data = $event->getData();
        $event->setData(array_values($data));
    }
}
