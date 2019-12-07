<?php
/**
 * Copyright © 2015, Ambroise Maupate and Julien Blanchet
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
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file NodeTypeFieldType.php
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Forms;

use Doctrine\Common\Persistence\ObjectManager;
use RZ\Roadiz\CMS\Forms\Constraints\NodeTypeField as NodeTypeFieldConstraint;
use RZ\Roadiz\CMS\Forms\Constraints\NonSqlReservedWord;
use RZ\Roadiz\CMS\Forms\Constraints\SimpleLatinString;
use RZ\Roadiz\CMS\Forms\Constraints\UniqueNodeTypeFieldName;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use RZ\Roadiz\Core\Entities\NodeType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class NodeTypeFieldType
 * @package Themes\Rozier\Forms
 */
class NodeTypeFieldType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', TextType::class, [
            'label' => 'name',
            'help' => 'technical_name_for_database_and_templating',
            'constraints' => [
                new NotBlank(),
                new UniqueNodeTypeFieldName([
                    'entityManager' => $options['em'],
                    'nodeType' => $options['nodeType'],
                    'currentValue' => $options['fieldName'],
                ]),
                new NonSqlReservedWord(),
                new SimpleLatinString(),
            ],
        ])
        ->add('label', TextType::class, [
            'label' => 'label',
            'help' => 'human_readable_field_name',
            'constraints' => [
                new NotBlank(),
            ],
        ])
        ->add('type', ChoiceType::class, [
            'label' => 'type',
            'required' => true,
            'choices' => array_flip(NodeTypeField::$typeToHuman),
        ])
        ->add('description', TextType::class, [
            'label' => 'description',
            'required' => false,
        ])
        ->add('placeholder', TextType::class, [
            'label' => 'placeholder',
            'required' => false,
            'help' => 'label_for_field_with_empty_data',
        ])
        ->add('groupName', TextType::class, [
            'label' => 'groupName',
            'required' => false,
            'help' => 'use_the_same_group_names_over_fields_to_gather_them_in_tabs',
        ])
        ->add('visible', CheckboxType::class, [
            'label' => 'visible',
            'required' => false,
            'help' => 'disable_field_visibility_if_you_dont_want_it_to_be_editable_from_backoffice',
        ])
        ->add('indexed', CheckboxType::class, [
            'label' => 'indexed',
            'required' => false,
            'help' => 'field_should_be_indexed_if_you_plan_to_query_or_order_by_it',
        ])
        ->add('universal', CheckboxType::class, [
            'label' => 'universal',
            'required' => false,
            'help' => 'universal_fields_will_be_only_editable_from_default_translation',
        ])
        ->add('expanded', CheckboxType::class, [
            'label' => 'expanded',
            'help' => 'use_checkboxes_or_radio_buttons_instead_of_select_box',
            'required' => false,
        ])
        ->add('defaultValues', DynamicType::class, [
            'label' => 'defaultValues',
            'required' => false,
            'help' => 'for_children_node_and_node_references_enter_node_type_names_comma_separated',
            'attr' => [
                'placeholder' => 'enter_values_comma_separated',
            ],
        ])
        ->add('minLength', IntegerType::class, [
            'label' => 'nodeTypeField.minLength',
            'required' => false,
        ])
        ->add('maxLength', IntegerType::class, [
            'label' => 'nodeTypeField.maxLength',
            'required' => false,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'nodetypefield';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label' => false,
            'fieldName' => '',
            'nodeType' => null,
            'data_class' => NodeTypeField::class,
            'attr' => [
                'class' => 'uk-form node-type-field-form',
            ],
            'constraints' => [
                new NodeTypeFieldConstraint(),
            ]
        ]);

        $resolver->setRequired([
            'nodeType',
            'em',
        ]);
        $resolver->setAllowedTypes('em', ObjectManager::class);
        $resolver->setAllowedTypes('fieldName', 'string');
        $resolver->setAllowedTypes('nodeType', NodeType::class);
    }
}
