<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file RolesType.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\CMS\Forms;

use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Role;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Roles selector form field type.
 */
class RolesType extends AbstractType
{
    protected $roles;

    /**
     * {@inheritdoc}
     * @param Doctrine\Common\Collections\ArrayCollection $roles Existing roles name array (used to display only available roles to parent entity)
     */
    public function __construct($roles = null)
    {
        $this->roles = $roles;
    }
    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $roles = Kernel::getInstance()->em()
            ->getRepository('RZ\Renzo\Core\Entities\Role')
            ->findAll();

        $choices = array();

        foreach ($roles as $role) {
            if (!$this->roles->contains($role)) {
                $choices[$role->getId()] = $role->getName();
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
        return 'roles';
    }
}