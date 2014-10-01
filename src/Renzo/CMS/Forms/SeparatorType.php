<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file SeparatorType.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\CMS\Forms;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Group selector form field type.
 */
class SeparatorType extends AbstractType
{
    
    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'required' => false
        ));
    }
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'separator';
    }
}
