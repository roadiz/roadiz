<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file UsersType.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\CMS\Forms;

use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Tag;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Users selector form field type.
 */
class UsersType extends AbstractType
{
    protected $users;
    /**
     * {@inheritdoc}
     *
     * @param Doctrine\Common\Collections\ArrayCollection $users
     */
    public function __construct($users = null)
    {
        $this->users = $users;
    }
    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $users = Kernel::getService('em')
            ->getRepository('RZ\Renzo\Core\Entities\User')
            ->findAll();

        $choices = array();
        foreach ($users as $user) {
            if (!$this->users->contains($user)) {
                $choices[$user->getId()] = $user->getUserName();
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
        return 'users';
    }
}
