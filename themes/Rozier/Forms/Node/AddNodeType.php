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
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Add node form type.
 *
 * @package Themes\Rozier\Forms\Node
 */
class AddNodeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', 'text', [
            'label' => 'title',
            'mapped' => false,
            'constraints' => [
                new NotBlank(),
            ],
        ])
        ->add('nodeType', new NodeTypesType(), [
            'label' => 'nodeType',
            'constraints' => [
                new NotBlank(),
            ],
        ])
        ->add('dynamicNodeName', 'checkbox', [
            'label' => 'node.dynamicNodeName',
            'required' => false,
            'attr' => ['class' => 'rz-boolean-checkbox'],
        ])
        ->add('visible', 'checkbox', [
            'label' => 'visible',
            'required' => false,
            'attr' => ['class' => 'rz-boolean-checkbox'],
        ])
        ->add('locked', 'checkbox', [
            'label' => 'locked',
            'required' => false,
            'attr' => ['class' => 'rz-boolean-checkbox'],
        ])
        ->add('hideChildren', 'checkbox', [
            'label' => 'hiding-children',
            'required' => false,
            'attr' => ['class' => 'rz-boolean-checkbox'],
        ])
        ->add('status', 'choice', [
            'label' => 'node.status',
            'required' => true,
            'choices_as_values' => true,
            'choices' => [
                'draft' => Node::DRAFT,
                'pending' => Node::PENDING,
                'published' => Node::PUBLISHED,
                'archived' => Node::ARCHIVED,
            ],
        ])
        ;

        $builder->get('nodeType')
                ->addModelTransformer(new NodeTypeTransformer($options['em']));
    }

    public function getName()
    {
        return 'childnode';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label' => false,
            'nodeName' => '',
            'attr' => [
                'class' => 'uk-form childnode-form',
            ],
        ]);

        $resolver->setRequired([
            'em',
        ]);

        $resolver->setAllowedTypes('em', 'Doctrine\Common\Persistence\ObjectManager');
        $resolver->setAllowedTypes('nodeName', 'string');
    }
}
