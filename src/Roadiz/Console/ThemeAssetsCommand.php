<?php
/**
 * Copyright (c) 2017. Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
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
 * @file ThemeAssetsCommand.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace RZ\Roadiz\Console;

use RZ\Roadiz\Core\Kernel;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ThemeAssetsCommand extends ThemesCommand
{
    protected function configure()
    {
        $this->setName('themes:assets:install')
            ->setDescription('Install a theme assets folder in public directory.')
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'Theme name (without the "Theme" suffix) or full-qualified ThemeApp class name (you can use / instead of \\).'
            )
            ->addOption('symlink', null, InputOption::VALUE_NONE, 'Symlinks the theme assets instead of copying it')
            ->addOption('relative', null, InputOption::VALUE_NONE, 'Make relative symlinks')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Kernel $kernel */
        $kernel = $this->getHelper('kernel')->getKernel();
        $name = $classname = str_replace('/', '\\', $input->getArgument('name'));
        $name = $this->validateThemeName($name);
        $themeName = $this->getThemeName($name);
        $output->writeln('Theme name is: <info>'. $themeName .'</info>.');
        $output->writeln('Theme assets are located in <info>'. $this->getThemePath($themeName) .'/static</info>.');

        if ($kernel->getRootDir() !== $kernel->getPublicDir()) {
            if ($input->getOption('relative')) {
                $expectedMethod = self::METHOD_RELATIVE_SYMLINK;
                $output->writeln('Trying to install theme assets as <info>relative symbolic link</info>.');
            } elseif ($input->getOption('symlink')) {
                $expectedMethod = self::METHOD_ABSOLUTE_SYMLINK;
                $output->writeln('Trying to install theme assets as <info>absolute symbolic link</info>.');
            } else {
                $expectedMethod = self::METHOD_COPY;
                $output->writeln('Installing theme assets as <info>hard copy</info>.');
            }

            $this->generateThemeSymlink($themeName, $expectedMethod);
        } else {
            throw new \RuntimeException('You are not using Roadiz Standard edition, no need to install your theme assets in public directory.');
        }
    }
}
