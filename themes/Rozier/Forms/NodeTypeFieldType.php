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

use RZ\Roadiz\CMS\Forms\Constraints\NonSqlReservedWord;
use RZ\Roadiz\CMS\Forms\Constraints\SimpleLatinString;
use RZ\Roadiz\CMS\Forms\Constraints\UniqueNodeTypeFieldName;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 *
 */
class NodeTypeFieldType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', 'text', [
            'label' => 'name',
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
        ->add('label', 'text', [
            'label' => 'label',
            'constraints' => [
                new NotBlank(),
            ],
        ])
        ->add('type', 'choice', [
            'label' => 'type',
            'required' => true,
            'choices' => array_flip(NodeTypeField::$typeToHuman),
            'choices_as_values' => true,
        ])
        ->add('description', 'text', [
            'label' => 'description',
            'required' => false,
        ])
        ->add('groupName', 'text', [
            'label' => 'groupName',
            'required' => false,
        ])
        ->add('visible', 'checkbox', [
            'label' => 'visible',
            'required' => false,
        ])
        ->add('indexed', 'checkbox', [
            'label' => 'indexed',
            'required' => false,
        ])
        ->add('universal', 'checkbox', [
            'label' => 'universal',
            'required' => false,
        ])
        ->add(
            'defaultValues',
            'text',
            [
                'label' => 'defaultValues',
                'required' => false,
                'attr' => [
                    'placeholder' => 'enter_values_comma_separated',
                ],
            ]
        )
        ->add(
            'minLength',
            'integer',
            [
                'label' => 'nodeTypeField.minLength',
                'required' => false,
            ]
        )
        ->add(
            'maxLength',
            'integer',
            [
                'label' => 'nodeTypeField.maxLength',
                'required' => false,
            ]
        );
    }

    public function getName()
    {
        return 'nodetypefield';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label' => false,
            'fieldName' => '',
            'nodeType' => null,
            'data_class' => 'RZ\Roadiz\Core\Entities\NodeTypeField',
            'attr' => [
                'class' => 'uk-form node-type-field-form',
            ],
        ]);

        $resolver->setRequired([
            'nodeType',
            'em',
        ]);
        $resolver->setAllowedTypes('em', 'Doctrine\Common\Persistence\ObjectManager');
        $resolver->setAllowedTypes('fieldName', 'string');
        $resolver->setAllowedTypes('nodeType', 'RZ\Roadiz\Core\Entities\NodeType');
    }
}
