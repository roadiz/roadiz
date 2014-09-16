<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file NodeTypesType.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\CMS\Forms;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\NodeType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Node types selector form field type.
 */
class NodeTypesType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $nodeTypes = Kernel::getInstance()->em()
            ->getRepository('RZ\Renzo\Core\Entities\NodeType')
            ->findAll();

        $choices = array();
        foreach ($nodeTypes as $nodeType) {
            $choices[$nodeType->getId()] = $nodeType->getDisplayName();
        }

        $resolver->setDefaults(array(
            'choices' => $choices
        ));
    }
    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'choice';
    }
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'NodeTypes';
    }
}
