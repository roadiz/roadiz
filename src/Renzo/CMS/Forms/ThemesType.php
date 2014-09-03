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
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Finder\Finder;

/**
 * Roles selector form field type.
 */
class ThemesType extends AbstractType
{
    protected $themes;

    /**
     * {@inheritdoc}
     * @param Doctrine\Common\Collections\ArrayCollection $roles Existing roles name array (used to display only available roles to parent entity)
     */
    public function __construct()
    {
    }
    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $themes = Kernel::getInstance()->em()
            ->getRepository('RZ\Renzo\Core\Entities\Theme')
            ->findAll();

        $choices = array();

        $finder = new Finder();

        // Extracting the PHP files from every Theme folder
        $iterator = $finder
            ->files()
            ->name('*App.php')
            ->depth(1)
            ->in(RENZO_ROOT.'/themes');

        // And storing it into an array, used in the form
        foreach ($iterator as $file) {
            ob_start();
            include_once RENZO_ROOT.'/themes/'.$file->getRelativePathname();
            $namespace = str_replace('/', '\\', $file->getRelativePathname());
            $classname = 'Themes\\'.str_replace('.php', '', $namespace);
            ob_end_clean();
            $choices[$classname] = $file->getFileName().": ".$classname::getThemeName();
        }
        foreach ($themes as $theme) {
            if (array_key_exists($theme->getClassName(), $choices)){
                unset($choices[$theme->getClassName()]);
            }
            if (array_key_exists(Kernel::INSTALL_CLASSNAME, $choices)){
                unset($choices[Kernel::INSTALL_CLASSNAME]);
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
        return 'classname';
    }
}