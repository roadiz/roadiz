<?php
/**
 * Copyright © 2016, Ambroise Maupate and Julien Blanchet
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
 * @file AddNodeType.php
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Forms\Node;

use RZ\Roadiz\CMS\Forms\NodeTypesType;
use RZ\Roadiz\CMS\Forms\DataTransformer\NodeTypeTransformer;
use RZ\Roadiz\Core\Entities\Node;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Add node form type.
 *
 * @package Themes\Rozier\Forms\Node
 */
class AddNodeType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', 'text', [
            'label' => 'title',
            'mapped' => false,
            'constraints' => [
                new NotBlank(),
                new Length([
                    'max' => 255
                ])
            ],
        ]);

        if ($options['showNodeType'] === true) {
            $builder->add('nodeType', new NodeTypesType($options['em']), [
                'label' => 'nodeType',
                'constraints' => [
                    new NotBlank(),
                ],
            ]);
            $builder->get('nodeType')
                ->addModelTransformer(new NodeTypeTransformer($options['em']));
        }

        $builder->add('dynamicNodeName', 'checkbox', [
            'label' => 'node.dynamicNodeName',
            'required' => false,
            'attr' => [
                'data-desc' => 'dynamic_node_name_will_follow_any_title_change_on_default_translation',
            ],
        ])
        ->add('visible', 'checkbox', [
            'label' => 'visible',
            'required' => false,
        ])
        ->add('locked', 'checkbox', [
            'label' => 'locked',
            'required' => false,
        ])
        ->add('hideChildren', 'checkbox', [
            'label' => 'hiding-children',
            'required' => false,
        ])
        ->add('status', 'choice', [
            'label' => 'node.status',
            'required' => true,
            'choices_as_values' => true,
            'choices' => [
                Node::getStatusLabel(Node::DRAFT) => Node::DRAFT,
                Node::getStatusLabel(Node::PENDING) => Node::PENDING,
                Node::getStatusLabel(Node::PUBLISHED) => Node::PUBLISHED,
                Node::getStatusLabel(Node::ARCHIVED) => Node::ARCHIVED,
            ],
        ])
        ;
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'childnode';
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label' => false,
            'nodeName' => '',
            'showNodeType' => true,
            'attr' => [
                'class' => 'uk-form childnode-form',
            ],
        ]);

        $resolver->setRequired([
            'em',
        ]);

        $resolver->setAllowedTypes('em', 'Doctrine\Common\Persistence\ObjectManager');
        $resolver->setAllowedTypes('nodeName', 'string');
        $resolver->setAllowedTypes('showNodeType', 'boolean');
    }
}
