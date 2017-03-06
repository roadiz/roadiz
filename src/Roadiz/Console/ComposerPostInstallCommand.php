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
 * @file ComposerPostInstallCommand.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */
namespace RZ\Roadiz\Console;

use RZ\Roadiz\Core\Kernel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Filesystem\Filesystem;

class ComposerPostInstallCommand extends Command
{
    /** @var Kernel|null  */
    protected $kernel = null;

    protected function configure()
    {
        $this
            ->setName('composer:post-install')
            ->setDescription('Perform file copy after Composer install.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->kernel = $this->getHelper('kernel')->getKernel();

        $this->executeConfig($input, $output);
        $this->executeProdEntryPoint($input, $output);
        $this->executePreviewEntryPoint($input, $output);
        $this->executeDevEntryPoint($input, $output);
        $this->executeInstallEntryPoint($input, $output);
        $this->executeClearCacheEntryPoint($input, $output);
        $this->executeVagrantfile($input, $output);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function executeConfig(InputInterface $input, OutputInterface $output)
    {
        $questionHelper = $this->getHelper('question');
        $fs = new Filesystem();

        $configFile = $this->kernel->getRootDir() . '/conf/config.yml';
        $configFileSrc = $this->kernel->getRootDir() . '/conf/config.default.yml';

        if (!$fs->exists($configFile) && $fs->exists($configFileSrc)) {
            $configQuestion = new ConfirmationQuestion('<question>Do you want to copy default configuration file?</question> [Y/n]', true);
            if ($questionHelper->ask(
                $input,
                $output,
                $configQuestion
            )) {
                $fs->copy($configFileSrc, $configFile);
            }
        }
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function executeProdEntryPoint(InputInterface $input, OutputInterface $output)
    {
        $questionHelper = $this->getHelper('question');
        $fs = new Filesystem();

        $indexFile = $this->kernel->getPublicDir() . '/index.php';
        $indexFileSrc = 'samples/index.php.sample';

        if (!$fs->exists($indexFile) && $fs->exists($indexFileSrc)) {
            $configQuestion = new ConfirmationQuestion('<question>Do you want to copy default production entry-point (index.php)?</question> [Y/n]', true);
            if ($questionHelper->ask(
                $input,
                $output,
                $configQuestion
            )) {
                $fs->copy($indexFileSrc, $indexFile);
                $output->writeln('<info>Production entry-point (index.php) copied</info> to ' . $this->kernel->getPublicDir());
            }
        }
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function executePreviewEntryPoint(InputInterface $input, OutputInterface $output)
    {
        $questionHelper = $this->getHelper('question');
        $fs = new Filesystem();

        $previewFile = $this->kernel->getPublicDir() . '/preview.php';
        $previewFileSrc = 'samples/preview.php.sample';

        if (!$fs->exists($previewFile) && $fs->exists($previewFileSrc)) {
            $configQuestion = new ConfirmationQuestion('<question>Do you want to copy default preview entry-point (preview.php)?</question> [Y/n]', true);
            if ($questionHelper->ask(
                $input,
                $output,
                $configQuestion
            )) {
                $fs->copy($previewFileSrc, $previewFile);
                $output->writeln('<info>Preview entry-point (preview.php) copied</info> to ' . $this->kernel->getPublicDir());
            }
        }
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function executeDevEntryPoint(InputInterface $input, OutputInterface $output)
    {
        $questionHelper = $this->getHelper('question');
        $fs = new Filesystem();

        $devFile = $this->kernel->getPublicDir() . '/dev.php';
        $devFileSrc = 'samples/dev.php.sample';

        /*
         * Copy config
         */
        if (!$fs->exists($devFile) && $fs->exists($devFileSrc)) {
            $configQuestion = new ConfirmationQuestion('<question>Do you want to copy default development entry-point (dev.php)?</question> [Y/n]', true);
            if ($questionHelper->ask(
                $input,
                $output,
                $configQuestion
            )) {
                $fs->copy($devFileSrc, $devFile);
                $output->writeln('<info>Development entry-point (dev.php) copied</info> to ' . $this->kernel->getPublicDir());
            }
        }
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function executeInstallEntryPoint(InputInterface $input, OutputInterface $output)
    {
        $questionHelper = $this->getHelper('question');
        $fs = new Filesystem();

        $installFile = $this->kernel->getPublicDir() . '/install.php';
        $installFileSrc = 'samples/install.php.sample';

        /*
         * Copy config
         */
        if (!$fs->exists($installFile) && $fs->exists($installFileSrc)) {
            $configQuestion = new ConfirmationQuestion('<question>Do you want to copy default install entry-point (install.php)?</question> [Y/n]', true);
            if ($questionHelper->ask(
                $input,
                $output,
                $configQuestion
            )) {
                $fs->copy($installFileSrc, $installFile);
                $output->writeln('<info>Install entry-point (install.php) copied</info> to ' . $this->kernel->getPublicDir());
            }
        }
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function executeClearCacheEntryPoint(InputInterface $input, OutputInterface $output)
    {
        $questionHelper = $this->getHelper('question');
        $fs = new Filesystem();

        $clearCacheFile = $this->kernel->getPublicDir() . '/clear_cache.php';
        $clearCacheFileSrc = 'samples/clear_cache.php.sample';

        /*
         * Copy config
         */
        if (!$fs->exists($clearCacheFile) && $fs->exists($clearCacheFileSrc)) {
            $configQuestion = new ConfirmationQuestion('<question>Do you want to copy default clearing cache entry-point (clear_cache.php)?</question> [Y/n]', true);
            if ($questionHelper->ask(
                $input,
                $output,
                $configQuestion
            )) {
                $fs->copy($clearCacheFileSrc, $clearCacheFile);
                $output->writeln('<info>Clearing cache entry-point (clear_cache.php) copied</info> to ' . $this->kernel->getPublicDir());
            }
        }
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function executeVagrantfile(InputInterface $input, OutputInterface $output)
    {
        $questionHelper = $this->getHelper('question');
        $fs = new Filesystem();

        /*
         * Need to copy to project root, not Kernel root!
         */
        $clearCacheFile = ROADIZ_ROOT . '/Vagrantfile';
        $clearCacheFileSrc = 'samples/Vagrantfile.sample';

        /*
         * Copy config
         */
        if (!$fs->exists($clearCacheFile) && $fs->exists($clearCacheFileSrc)) {
            $configQuestion = new ConfirmationQuestion('<question>Do you want to copy a Vagrantfile sample for development VM (Vagrantfile)?</question> [Y/n]', true);
            if ($questionHelper->ask(
                $input,
                $output,
                $configQuestion
            )) {
                $fs->copy($clearCacheFileSrc, $clearCacheFile);
                $output->writeln('<info>Vagrantfile sample copied</info> to ' . ROADIZ_ROOT);
            }
        }
    }
}
