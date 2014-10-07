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
 * Theme selector form field type.
 */
class ThemesType extends AbstractType
{
    protected $themes;

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $themes = Kernel::getService('em')
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
            $classPath = RENZO_ROOT.'/themes/'.$file->getRelativePathname();
            include_once $classPath;
            $namespace = str_replace('/', '\\', $file->getRelativePathname());
            $classname = '\Themes\\'.str_replace('.php', '', $namespace);
            ob_end_clean();

            /*
             * Parsed file is not or does not contain any PHP Class
             * Bad Theme !
             */
            if (class_exists($classname)) {
                $choices[$classname] = $file->getFileName().": ".$classname::getThemeName();
            } else {
                throw new ThemeClassNotValidException($classPath . " file does not contain any valid PHP Class.", 1);
            }
        }
        foreach ($themes as $theme) {
            if (array_key_exists($theme->getClassName(), $choices)) {
                unset($choices[$theme->getClassName()]);
            }
            if (array_key_exists(Kernel::INSTALL_CLASSNAME, $choices)) {
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
