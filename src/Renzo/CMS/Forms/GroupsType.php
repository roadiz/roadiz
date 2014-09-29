<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file GroupsType.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\CMS\Forms;

use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Group;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Group selector form field type.
 */
class GroupsType extends AbstractType
{
    protected $groups;
    /**
     * {@inheritdoc}
     */
    public function __construct($groups = null)
    {
        $this->groups = $groups;
    }
    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $groups = Kernel::getService('em')
            ->getRepository('RZ\Renzo\Core\Entities\Group')
            ->findAll();

        $choices = array();
        foreach ($groups as $group) {
            if (!$this->groups->contains($group)) {
                $choices[$group->getId()] = $group->getName();
            }
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
        return 'groups';
    }
}
