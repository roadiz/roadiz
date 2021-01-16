<?php
declare(strict_types=1);

namespace RZ\Roadiz\Attribute\Form;

use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\CMS\Forms\Constraints\UniqueEntity;
use RZ\Roadiz\Core\Entities\AttributeGroup;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

class AttributeGroupType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('canonicalName', TextType::class, [
                'label' => 'attribute_group.form.canonicalName',
                'constraints' => [
                    new NotNull(),
                    new NotBlank(),
                ]
            ])
            ->add('attributeGroupTranslations', CollectionType::class, [
                'label' => 'attribute_group.form.attributeGroupTranslations',
                'allow_add' => true,
                'required' => false,
                'allow_delete' => true,
                'entry_type' => AttributeGroupTranslationType::class,
                'by_reference' => false,
                'entry_options' => [
                    'label' => false,
                    'attr' => [
                        'class' => 'uk-form uk-form-horizontal'
                    ]
                ],
                'attr' => [
                    'class' => 'rz-collection-form-type'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('data_class', AttributeGroup::class);
        $resolver->setDefault('constraints', [
            new UniqueEntity([
                'fields' => ['canonicalName'],
            ])
        ]);
    }
}
