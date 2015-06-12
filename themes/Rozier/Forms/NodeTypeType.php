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
 * @file NodeTypeType.php
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Forms;

use RZ\Roadiz\CMS\Forms\Constraints\HexadecimalColor;
use RZ\Roadiz\CMS\Forms\Constraints\NonSqlReservedWord;
use RZ\Roadiz\CMS\Forms\Constraints\SimpleLatinString;
use RZ\Roadiz\CMS\Forms\Constraints\UniqueNodeTypeName;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 *
 */
class NodeTypeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (empty($options['name'])) {
            $builder->add('name', 'text', [
                'label' => 'name',
                'constraints' => [
                    new NotBlank(),
                    new NonSqlReservedWord(),
                    new SimpleLatinString(),
                    new UniqueNodeTypeName([
                        'entityManager' => $options['em'],
                        'currentValue' => $options['name'],
                    ]),
                ],
            ]);
        }
        $builder->add('displayName', 'text', [
                    'label' => 'nodeType.displayName',
                    'constraints' => [
                        new NotBlank(),
                    ],
                ])
                ->add('description', 'text', [
                    'label' => 'description',
                    'required' => false,
                ])
                ->add('visible', 'checkbox', [
                    'label' => 'visible',
                    'required' => false,
                ])
                ->add('newsletterType', 'checkbox', [
                    'label' => 'nodeType.newsletterType',
                    'required' => false,
                ])
                ->add('hidingNodes', 'checkbox', [
                    'label' => 'nodeType.hidingNodes',
                    'required' => false,
                ])
                ->add('color', 'text', [
                    'label' => 'nodeType.color',
                    'required' => false,
                    'attr' => ['class' => 'colorpicker-input'],
                    'constraints' => [
                        new HexadecimalColor(),
                    ],
                ]);
    }

    public function getName()
    {
        return 'nodetypefield';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label' => false,
            'name' => '',
            'data_class' => 'RZ\Roadiz\Core\Entities\NodeType',
            'attr' => [
                'class' => 'uk-form node-type-form',
            ],
        ]);

        $resolver->setRequired([
            'em',
        ]);
        $resolver->setAllowedTypes('em', 'Doctrine\Common\Persistence\ObjectManager');
        $resolver->setAllowedTypes('name', 'string');
    }
}
