<?php
declare(strict_types=1);
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

use Doctrine\Common\Collections\ArrayCollection;
use RZ\Roadiz\Config\ConfigurationHandler;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Utils\Clearer\ConfigurationCacheClearer;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

class ThemeGenerateCommand extends ThemesCommand
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
            )
            ->addOption('symlink', null, InputOption::VALUE_NONE, 'Symlinks the theme assets instead of copying it')
            ->addOption('relative', null, InputOption::VALUE_NONE, 'Make relative symlinks')
        ;
    }

    /**
     * @param $name
     * @return string
     */
    public function validateThemeName(string $name)
    {
        /** @var Kernel $kernel */
        $kernel = $this->getHelper('kernel')->getKernel();
        if (1 !== preg_match('#^[A-Z][a-zA-Z]+$#', $name)) {
            throw new LogicException('Theme name must only contain alphabetical characters and begin with uppercase letter.');
        }

        if (1 === preg_match('#[Tt]heme$#', $name)) {
            throw new LogicException('Theme name must not contain "Theme" suffix, it will be added automatically.');
        }

        if ($this->filesystem->exists($kernel->getProjectDir() . '/themes/' . $name . 'Theme')) {
            throw new LogicException('Theme already exists.');
        }

        if (in_array($name, ['Default', 'Debug', 'Base', 'Install', 'Rozier'])) {
            throw new LogicException('You cannot name your theme after system themes (Default, Install, Base, Rozier or Debug).');
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
        /** @var string $name */
        $name = $this->validateThemeName($input->getArgument('name'));
        $io = new SymfonyStyle($input, $output);
        $themeName = $this->getThemeName($name);
        $themePath = $this->getNewThemePath($themeName);
        $branch = 'master';

        if ($input->getOption('develop')) {
            $branch = 'develop';
        }
        if ($input->getOption('branch')) {
            $branch = $input->getOption('branch');
        }
        $io->writeln('Using <info>'.$branch.'</info> branch.');

        /*
         * Clone BaseTheme
         */
        $repository = 'https://github.com/roadiz/BaseTheme.git';
        $process = new Process(
            ['git', 'clone', '-b', $branch, $repository, $themePath]
        );
        $process->run();
        $io->writeln('BaseTheme cloned into <info>' . $themePath . '</info>');

        /*
         * Remove existing Git history.
         */
        $this->filesystem->remove($themePath . '/.git');
        $io->writeln('Remove Git history.');

        /*
         * Rename main theme class.
         */
        $this->filesystem->rename($themePath . '/BaseThemeApp.php', $themePath . '/' . $name . 'ThemeApp.php');
        $io->writeln('Rename main theme class.');
        $this->filesystem->rename($themePath . '/Services/BaseThemeServiceProvider.php', $themePath . '/Services/' . $name . 'ThemeServiceProvider.php');
        $io->writeln('Rename theme service provider class.');

        /*
         * Rename every occurrences of BaseTheme in your theme.
         */
        $processes = new ArrayCollection();
        $processes->add(new Process(
            [
                'find', $themePath, '-type', 'f', '-exec', 'sed', '-i.bak',
                '-e', 's/BaseTheme/' . $name . 'Theme/g', '{}', ';',
            ],
            null,
            ['LC_ALL' => 'C']
        ));
        $processes->add(new Process(
            [
                'find', $themePath, '-type', 'f', '-exec', 'sed', '-i.bak',
                '-e', 's/Base theme/' . $name . ' theme/g', '{}', ';',
            ],
            null,
            ['LC_ALL' => 'C']
        ));
        $processes->add(new Process(
            [
                'find', $themePath . '/static', '-type', 'f', '-exec', 'sed', '-i.bak',
                '-e', 's/Base/' . $name . '/g', '{}', ';',
            ],
            null,
            ['LC_ALL' => 'C']
        ));
        $processes->add(new Process(
            [
                'find', $themePath , '-type', 'f', '-name', '*.bak', '-exec', 'rm', '-f', '{}', ';',
            ],
            null,
            ['LC_ALL' => 'C']
        ));
        $io->writeln('Rename every occurrences of BaseTheme in your theme.');
        /** @var Process $process */
        foreach ($processes as $process) {
            $process->run();
        }

        if ($input->getOption('relative')) {
            $expectedMethod = self::METHOD_RELATIVE_SYMLINK;
            $io->writeln('Trying to install theme assets as <info>relative symbolic link</info>.');
        } elseif ($input->getOption('symlink')) {
            $expectedMethod = self::METHOD_ABSOLUTE_SYMLINK;
            $io->writeln('Trying to install theme assets as <info>absolute symbolic link</info>.');
        } else {
            $expectedMethod = self::METHOD_COPY;
            $io->writeln('Installing theme assets as <info>hard copy</info>.');
        }
        $this->generateThemeSymlink($themeName, $expectedMethod);

        $io->writeln('Register Theme into your configuration.');
        $this->registerTheme($themeName);

        $io->success('Your new theme is ready to install, have fun!');

        return 0;
    }

    /**
     * @param string $themeName
     */
    protected function registerTheme(string $themeName)
    {
        $className = '\\Themes\\'.$themeName.'\\'.$themeName. 'App';
        /** @var ConfigurationHandler $configHandler */
        $configHandler = $this->getHelper('configurationHandler')->getConfigurationHandler();
        /** @var Kernel $kernel */
        $kernel = $this->getHelper('kernel')->getKernel();
        /** @var array $config */
        $config = $configHandler->getConfiguration();
        $config['themes'][] = [
            'classname' => $className,
            'hostname' => '*',
            'routePrefix' => '',
        ];
        $configHandler->setConfiguration($config);
        $configHandler->writeConfiguration();
        /*
         * Need to clear configuration cache.
         */
        $configurationClearer = new ConfigurationCacheClearer($kernel->getCacheDir());
        $configurationClearer->clear();
    }
}
