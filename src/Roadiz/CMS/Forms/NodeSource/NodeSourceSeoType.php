<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms\NodeSource;

use RZ\Roadiz\Core\Entities\NodesSources;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

/**
 * @package RZ\Roadiz\CMS\Forms\NodeSource
 */
final class NodeSourceSeoType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('metaTitle', TextType::class, [
                'label' => 'metaTitle',
                'help' => 'nodeSource.metaTitle.help',
                'required' => false,
                'attr' => [
                    'data-max-length' => 60,
                ],
                'constraints' => [
                    new Length([
                        'max' => 60
                    ])
                ]
            ])
            ->add('metaDescription', TextareaType::class, [
                'label' => 'metaDescription',
                'help' => 'nodeSource.metaDescription.help',
                'required' => false,
            ])
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'class' => NodesSources::class,
            'property' => 'id',
        ]);
    }
}
