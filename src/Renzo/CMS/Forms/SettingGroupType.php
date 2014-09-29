<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file ThemesType.php
 * @copyright REZO ZERO 2014
 * @author Maxime Constantinian
 */
namespace RZ\Renzo\CMS\Forms;

use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Theme;
use RZ\Renzo\Core\Exceptions\ThemeClassNotValidException;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Finder\Finder;

/**
 * Group setting selector form field type.
 */
class SettingGroupType extends AbstractType
{
    protected $themes;

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $groups = Kernel::getService('em')
            ->getRepository('RZ\Renzo\Core\Entities\SettingGroup')
            ->findAll();

        $choices = array();

        foreach ($groups as $group) {
            $choices[$group->getId()] = $group->getName();
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
        return 'settingGroups';
    }
}
