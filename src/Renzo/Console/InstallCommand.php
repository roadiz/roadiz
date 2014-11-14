<?php
/**
 * Copyright © 2014, REZO ZERO
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
 * Except as contained in this notice, the name of the REZO ZERO shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from the REZO ZERO SARL.
 *
 * @file InstallCommand.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Console;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Theme;
use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Console\SchemaCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command line utils for installing RZ-CMS v3 from terminal.
 */
class InstallCommand extends Command
{
    private $dialog;

    protected function configure()
    {
        $this
            ->setName('install')
            ->setDescription('First install database and default backend theme');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->dialog = $this->getHelperSet()->get('dialog');
        $text="";

        if ($this->dialog->askConfirmation(
            $output,
            '<question>Are you sure to perform installation?</question> : ',
            false
        )) {

            if (SchemaCommand::updateSchema()) {
                $text .= '<info>Schema updated…</info>'.PHP_EOL;

                /*
                 * Create backend theme
                 */
                if (!$this->hasDefaultBackend()) {

                    $theme = new Theme();
                    $theme->setAvailable(true)
                        ->setBackendTheme(true)
                        ->setClassName("Themes\Rozier\RozierApp");

                    Kernel::getService('em')->persist($theme);
                    Kernel::getService('em')->flush();

                    $text .= '<info>Rozier back-end theme installed…</info>'.PHP_EOL;
                } else {
                    $text .= '<error>A back-end theme is already installed.</error>'.PHP_EOL;
                }

                /*
                 * Create default translation
                 */
                if (!$this->hasDefaultTranslation()) {

                    $defaultTrans = new Translation();
                    $defaultTrans
                        ->setDefaultTranslation(true)
                        ->setLocale("en_GB")
                        ->setName("Default translation");

                    Kernel::getService('em')->persist($defaultTrans);
                    Kernel::getService('em')->flush();

                    $text .= '<info>Default translation installed…</info>'.PHP_EOL;
                } else {
                    $text .= '<error>A default translation is already installed.</error>'.PHP_EOL;
                }
            }
        }

        $output->writeln($text);
    }

    private function hasDefaultBackend()
    {
        $default = Kernel::getService('em')
            ->getRepository("RZ\Renzo\Core\Entities\Theme")
            ->findOneBy(array("backendTheme"=>true));

        return $default !== null ? true : false;
    }

    /**
     * Tell if there is any translation.
     *
     * @return boolean
     */
    public function hasDefaultTranslation()
    {
        $default = Kernel::getService('em')
            ->getRepository("RZ\Renzo\Core\Entities\Translation")
            ->findOneBy(array());

        return $default !== null ? true : false;
    }
}
