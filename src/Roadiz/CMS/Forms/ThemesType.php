<?php
/**
 * Copyright © 2014, Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file ThemesType.php
 * @author Maxime Constantinian
 */
namespace RZ\Roadiz\CMS\Forms;

use RZ\Roadiz\Core\Kernel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Finder\Finder;
use RZ\Roadiz\Console\Tools\YamlConfiguration;

/**
 * Theme selector form field type.
 */
class ThemesType extends AbstractType
{
    protected $themes;
    private $choices;

    public function __construct()
    {
        $themes = Kernel::getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\Theme')
            ->findAll();

        $choices = [];

        $finder = new Finder();

        // Extracting the PHP files from every Theme folder
        $iterator = $finder
            ->followLinks()
            ->files()
            ->name('config.yml')
            ->depth(1)
            ->in(ROADIZ_ROOT.'/themes');

        // And storing it into an array, used in the form
        foreach ($iterator as $file) {
            $yaml = new YamlConfiguration($file->getPathname());
            $yaml->load();

            $data = $yaml->getConfiguration();

            $classname = '\Themes\\'.$data['themeDir']."\\".$data['themeDir']."App";

            /*
             * Parsed file is not or does not contain any PHP Class
             * Bad Theme !
             */
            $choices[$classname] = $data['name'];

        }
        foreach ($themes as $theme) {
            if (array_key_exists($theme->getClassName(), $choices)) {
                unset($choices[$theme->getClassName()]);
            }
            if (array_key_exists(Kernel::INSTALL_CLASSNAME, $choices)) {
                unset($choices[Kernel::INSTALL_CLASSNAME]);
            }
        }
        $this->choices = $choices;
    }

    public function getSize()
    {
        return (count($this->choices));
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'choices' => $this->choices
        ]);
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
