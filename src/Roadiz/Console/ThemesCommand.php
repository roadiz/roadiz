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
 * @file ThemesCommand.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Console;

use RZ\Roadiz\Core\Kernel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command line utils for managing themes from terminal.
 */
class ThemesCommand extends Command
{
    protected function configure()
    {
        $this->setName('core:themes')
            ->setDescription('Manage themes')
            ->addArgument(
                'classname',
                InputArgument::OPTIONAL,
                'Main theme classname'
            )
            ->addOption(
                'setup',
                null,
                InputOption::VALUE_NONE,
                'Setup theme'
            )
            ->addOption(
                'disable',
                null,
                InputOption::VALUE_NONE,
                'Disable theme'
            )
            ->addOption(
                'force-twig-compilation',
                null,
                InputOption::VALUE_NONE,
                'Force Twig templates compilation'
            )
            ->addOption(
                'enable',
                null,
                InputOption::VALUE_NONE,
                'Enable theme'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $text="";
        $name = $input->getArgument('classname');

        if ($name) {
            $theme = Kernel::getService('em')
                ->getRepository('RZ\Roadiz\Core\Entities\Theme')
                ->findOneBy(array('className'=>$name));

            if ($theme !== null) {
                if ($input->getOption('enable')) {
                    if ($theme !== null && $name::enable()) {
                        $text = '<info>Theme enabled…</info>'.PHP_EOL;
                    } else {
                        $text = '<error>Requested theme is not setup yet…</error>'.PHP_EOL;
                    }
                }
                if ($input->getOption('disable')) {
                    if ($theme !== null && $name::disable()) {
                        $text = '<info>Theme disabled…</info>'.PHP_EOL;
                    } else {
                        $text = '<error>Requested theme is not setup yet…</error>'.PHP_EOL;
                    }
                }

                if ($input->getOption('force-twig-compilation')) {
                    if (true === $name::forceTwigCompilation()) {
                        $text = '<info>Twig templates have been compiled…</info>'.PHP_EOL;
                    }
                }
            } else {
                if ($name::setup() === true) {
                    $text = '<info>Theme setup sucessfully…</info>'.PHP_EOL;
                } else {
                    $text = '<error>Cannot setup theme…</error>'.PHP_EOL;
                }
            }
        } else {
            $text = '<info>Installed theme…</info>'.PHP_EOL;
            $themes = Kernel::getService('em')
                ->getRepository('RZ\Roadiz\Core\Entities\Theme')
                ->findAll();

            if (count($themes) > 0) {
                $text .= ' | '.PHP_EOL;
                foreach ($themes as $theme) {
                    $text .=
                        ' |_ '.$theme->getClassName()
                        .' — <info>'.($theme->isAvailable()?'enabled':'disabled').'</info>'
                        .' — <comment>'.($theme->isBackendTheme()?'Backend':'Frontend').'</comment>'
                        .PHP_EOL;
                }
            } else {
                $text = '<info>No available themes</info>'.PHP_EOL;
            }
        }

        $output->writeln($text);
    }
}
