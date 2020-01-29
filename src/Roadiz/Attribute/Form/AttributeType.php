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
 * @file AttributeType.php
 * @author Ambroise Maupate
 *
 */
declare(strict_types=1);

namespace RZ\Roadiz\Attribute\Form;

use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\Attribute\Form\AttributeDocumentType;
use RZ\Roadiz\Attribute\Model\AttributeInterface;
use RZ\Roadiz\CMS\Forms\ColorType;
use RZ\Roadiz\CMS\Forms\Constraints\UniqueEntity;
use RZ\Roadiz\Core\Entities\Attribute;
use RZ\Roadiz\Core\Entities\AttributeGroup;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

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
                    new NotBlank(),
                    new Regex([
                        'pattern' => "/^[a-z_]+$/i",
                        'htmlPattern' => "^[a-z_]+$",
                        'message' => 'attribute_code.must_contain_alpha_underscore'
                    ])
                ]
            ])
            ->add('group', ChoiceType::class, [
                'label' => 'attributes.form.group',
                'required' => false,
                'help' => 'attributes.form_help.group',
                'data_class' => AttributeGroup::class,
                'placeholder' => 'attributes.form.group.placeholder'
            ])
            ->add('color', ColorType::class, [
                'label' => 'attributes.form.color',
                'help' => 'attributes.form_help.color'
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
                    'entityManager' => $options['entityManager'],
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
