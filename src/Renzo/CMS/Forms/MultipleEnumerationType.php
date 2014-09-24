<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file MultipleEnumerationType.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\CMS\Forms;

use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\NodeTypeField;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Group selector form field type.
 */
class MultipleEnumerationType extends AbstractType
{
    protected $field;
    /**
     * {@inheritdoc}
     */
    public function __construct(NodeTypeField $field)
    {
        $this->field = $field;
    }
    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $choices = array();
        $values = explode(',', $this->field->getDefaultValues());

        foreach ($values as $value) {
            $value = trim($value);
            $choices[$value] = $value;
        }

        $resolver->setDefaults(array(
            'choices' => $choices,
            'multiple' => true
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
        return 'enumeration';
    }
}
