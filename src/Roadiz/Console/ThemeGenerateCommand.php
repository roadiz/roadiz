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
 * @file ThemeGenerateCommand.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */
namespace RZ\Roadiz\Console;

use RZ\Roadiz\Core\Kernel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\ProcessBuilder;

class ThemeGenerateCommand extends Command
{
    protected function configure()
    {
        $this->setName('themes:generate')
            ->setDescription('Generate a new theme based on BaseTheme boilerplate. <info>Requires "find", "sed" and "git" commands.</info>')
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'Theme name (without the "Theme" suffix)'
            )
            ->addOption(
                'develop',
                'd',
                InputOption::VALUE_NONE,
                'Use BaseTheme develop branch instead of master.'
            )
            ->addOption(
                'branch',
                'b',
                InputOption::VALUE_REQUIRED,
                'Choose BaseTheme branch.'
            );
    }

    /**
     * @param $name
     * @return string
     */
    public function validateThemeName($name)
    {
        $filesystem = new Filesystem();

        if (1 !== preg_match('#^[A-Z][a-zA-Z]+$#', $name)) {
            throw new \RuntimeException('Theme name must only contain alphabetical characters and begin with uppercase letter.');
        }

        if (1 === preg_match('#[Tt]heme$#', $name)) {
            throw new \RuntimeException('Theme name must not contain "Theme" suffix, it will be added automatically.');
        }

        if ($filesystem->exists(ROADIZ_ROOT . '/themes/' . $name . 'Theme')) {
            throw new \RuntimeException('Theme already exists.');
        }

        if (in_array($name, ['Default', 'Base', 'Install', 'Rozier'])) {
            throw new \RuntimeException('You cannot name your theme after system themes (Default, Install, Base, Rozier).');
        }

        return $name;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $this->validateThemeName($input->getArgument('name'));
        $themeName = $name . 'Theme';
        $themePath = ROADIZ_ROOT . '/themes/' . $themeName;
        $branch = 'master';
        $filesystem = new Filesystem();

        if ($input->getOption('develop')) {
            $branch = 'develop';
            $output->writeln('Using <info>develop</info> branch.');
        }

        if ($input->getOption('branch')) {
            $branch = $input->getOption('branch');
            $output->writeln('Using <info>'.$branch.'</info> branch.');
        }

        /*
         * Clone BaseTheme
         */
        $builder = new ProcessBuilder([
            'git',
            'clone',
            '-b',
            $branch,
            'https://github.com/roadiz/BaseTheme.git',
            $themePath
        ]);
        $builder->getProcess()->run();
        $output->writeln('BaseTheme cloned into ' . $themePath);

        /*
         * Remove existing Git history.
         */
        $filesystem->remove($themePath . '/.git');
        $output->writeln('Remove Git history.');

        /*
         * Rename main theme class.
         */
        $filesystem->rename($themePath . '/BaseThemeApp.php', $themePath . '/' . $name . 'ThemeApp.php');
        $output->writeln('Rename main theme class.');

        /*
         * Rename every occurrences of BaseTheme in your theme.
         */
        $builder = new ProcessBuilder();
        $builder->setEnv('LC_ALL', 'C');
        $builder->setPrefix(['find']);

        $builder->setArguments([
            $themePath, '-type', 'f', '-exec', 'sed', '-i.bak',
            '-e', 's/BaseTheme/' . $name . 'Theme/g', '{}', ';'
        ]);
        $builder->getProcess()->run();

        $builder->setArguments([
            $themePath, '-type', 'f', '-exec', 'sed', '-i.bak',
            '-e', 's/Base theme/' . $name . ' theme/g', '{}', ';'
        ]);
        $builder->getProcess()->run();

        $builder->setArguments([
            $themePath . '/static', '-type', 'f', '-exec', 'sed', '-i.bak',
            '-e', 's/Base/' . $name . '/g', '{}', ';'
        ]);
        $builder->getProcess()->run();

        $builder->setArguments([
            $themePath , '-type', 'f', '-name', '*.bak', '-exec', 'rm', '-f', '{}', ';'
        ]);
        $builder->getProcess()->run();

        $output->writeln('Rename every occurrences of BaseTheme in your theme.');

        /*
         * Create a symlink if using standard edition.
         */
        /** @var Kernel $kernel */
        $kernel = $this->getHelper('kernel')->getKernel();
        if ($kernel->getRootDir() !== $kernel->getPublicDir()) {
            $filesystem->mkdir($kernel->getPublicDir() . '/themes/' . $themeName);
            $filesystem->symlink('../../../themes/' . $themeName . '/static', $kernel->getPublicDir() . '/themes/' . $themeName . '/static');
            $output->writeln('Create a <info>relative</info> symlink in web/ dir.');
        }

        $output->writeln('<info>Your new theme is ready to install, have fun!</info>');
    }
}
