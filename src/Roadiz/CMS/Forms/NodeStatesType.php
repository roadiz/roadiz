<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms;

use RZ\Roadiz\Core\Entities\Node;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Node state selector form field type.
 */
class NodeStatesType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $choices = [];
        $choices[Node::getStatusLabel(Node::DRAFT)] = Node::DRAFT;
        $choices[Node::getStatusLabel(Node::PENDING)] = Node::PENDING;
        $choices[Node::getStatusLabel(Node::PUBLISHED)] = Node::PUBLISHED;
        $choices[Node::getStatusLabel(Node::ARCHIVED)] = Node::ARCHIVED;
        $choices[Node::getStatusLabel(Node::DELETED)] = Node::DELETED;

        $resolver->setDefaults([
            'choices' => $choices,
            'placeholder' => 'ignore',
        ]);
    }
    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ChoiceType::class;
    }
    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'node_statuses';
    }
}
