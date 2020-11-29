<?php
declare(strict_types=1);

namespace RZ\Roadiz\Attribute\Form;

use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\Attribute\Model\AttributeInterface;
use RZ\Roadiz\CMS\Forms\ColorType;
use RZ\Roadiz\CMS\Forms\Constraints\UniqueEntity;
use RZ\Roadiz\Core\Entities\Attribute;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

class AttributeType extends AbstractType
{
    /**
     * @inheritDoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('code', TextType::class, [
                'label' => 'attributes.form.code',
                'required' => true,
                'help' => 'attributes.form_help.code',
                'constraints' => [
                    new NotNull(),
                    new NotBlank()
                ]
            ])
            ->add('group', AttributeGroupsType::class, [
                'label' => 'attributes.form.group',
                'required' => false,
                'help' => 'attributes.form_help.group',
                'placeholder' => 'attributes.form.group.placeholder',
                'entityManager' => $options['entityManager']
            ])
            ->add('color', ColorType::class, [
                'label' => 'attributes.form.color',
                'help' => 'attributes.form_help.color',
                'required' => false,
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'attributes.form.type',
                'required' => true,
                'choices' => [
                    'attributes.form.type.string' => AttributeInterface::STRING_T,
                    'attributes.form.type.datetime' => AttributeInterface::DATETIME_T,
                    'attributes.form.type.boolean' => AttributeInterface::BOOLEAN_T,
                    'attributes.form.type.integer' => AttributeInterface::INTEGER_T,
                    'attributes.form.type.decimal' => AttributeInterface::DECIMAL_T,
                    'attributes.form.type.percent' => AttributeInterface::PERCENT_T,
                    'attributes.form.type.email' => AttributeInterface::EMAIL_T,
                    'attributes.form.type.colour' => AttributeInterface::COLOUR_T,
                    'attributes.form.type.enum' => AttributeInterface::ENUM_T,
                    'attributes.form.type.date' => AttributeInterface::DATE_T,
                    'attributes.form.type.country' => AttributeInterface::COUNTRY_T,
                ],
            ])
            ->add('searchable', CheckboxType::class, [
                'label' => 'attributes.form.searchable',
                'required' => false,
                'help' => 'attributes.form_help.searchable'
            ])
            ->add('attributeTranslations', CollectionType::class, [
                'label' => 'attributes.form.attributeTranslations',
                'allow_add' => true,
                'required' => false,
                'allow_delete' => true,
                'entry_type' => AttributeTranslationType::class,
                'by_reference' => false,
                'entry_options' => [
                    'label' => false,
                    'entityManager' => $options['entityManager'],
                    'attr' => [
                        'class' => 'uk-form uk-form-horizontal'
                    ]
                ],
                'attr' => [
                    'class' => 'rz-collection-form-type'
                ]
            ])
            ->add('attributeDocuments', AttributeDocumentType::class, [
                'label' => 'attributes.form.documents',
                'help' => 'attributes.form_help.documents',
                'required' => false,
                'attribute' => $builder->getForm()->getData(),
                'entityManager' => $options['entityManager'],
            ])
        ;
    }

    /**
     * @inheritDoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefault('data_class', Attribute::class);
        $resolver->setRequired('entityManager');
        $resolver->setAllowedTypes('entityManager', [EntityManagerInterface::class]);

        $resolver->setNormalizer('constraints', function (Options $options) {
            return [
                new UniqueEntity([
                    'fields' => ['code'],
                ])
            ];
        });
    }

    /**
     * @inheritDoc
     */
    public function getBlockPrefix()
    {
        return 'attribute';
    }
}
