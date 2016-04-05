<?php
/**
 * Copyright (c) Rezo Zero 2016.
 *
 * prison-insider
 *
 * Created on 05/04/16 11:27
 *
 * @author ambroisemaupate
 * @file ChildNodeType.php
 */

namespace Themes\Rozier\Forms\Node;

use RZ\Roadiz\CMS\Forms\Constraints\UniqueNodeName;
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
