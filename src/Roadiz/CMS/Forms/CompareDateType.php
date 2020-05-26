<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CompareDateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('compareOp', ChoiceType::class, [
            'label' => false,
            'choices' => [
                '<' => '<',
                '>' => '>',
                '<=' => '<=',
                '>=' => '>=',
                '=' => '='
            ]
        ])
        ->add('compareDate', DateType::class, [
            'label' => false,
            'required' => false,
            'widget' => 'single_text',
            'format' => 'yyyy-MM-dd',
            'attr' => [
                'class' => 'rz-datetime-field',
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'required' => false,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'comparedate';
    }
}
