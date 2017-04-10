<?php

namespace Themes\Rozier\Forms;

use RZ\Roadiz\CMS\Forms\Constraints\ValidYaml;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Dynamic editor form field type.
 */
class DynamicType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'textarea';
    }
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'dynamic';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'required' => false,
            'attr' => [
                'class' => 'dynamic_textarea',
            ],
            'constraints' => [
                new ValidYaml()
            ]
        ]);
    }
}
