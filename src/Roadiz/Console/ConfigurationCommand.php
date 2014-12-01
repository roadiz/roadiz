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
namespace RZ\Roadiz\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use RZ\Roadiz\Console\Tools\Configuration;

/**
 * Command line utils for installing RZ-CMS v3 from terminal.
 */
class ConfigurationCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('config')
            ->setDescription('Manage configuration from CLI')
            ->addOption(
                'enable-devmode',
                null,
                InputOption::VALUE_NONE,
                'Enable the devMode flag for your application'
            )
            ->addOption(
                'disable-devmode',
                null,
                InputOption::VALUE_NONE,
                'Disable the devMode for your application'
            )
            ->addOption(
                'enable-install',
                null,
                InputOption::VALUE_NONE,
                'Enable the install assistant'
            )
            ->addOption(
                'disable-install',
                null,
                InputOption::VALUE_NONE,
                'Disable the install assistant'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $text="";

        $configuration = new Configuration();

        if ($input->getOption('enable-devmode')) {
            $configuration->setDevMode(true);
            $configuration->writeConfiguration();

            $text .= '<info>Dev mode has been changed to true</info>'.PHP_EOL;
        }
        if ($input->getOption('disable-devmode')) {
            $configuration->setDevMode(false);
            $configuration->writeConfiguration();

            $text .= '<info>Dev mode has been changed to false</info>'.PHP_EOL;
            $text .= 'Do not forget to empty all cache and purge XCache/APC caches manually.'.PHP_EOL;
        }

        if ($input->getOption('enable-install')) {

            $configuration->setInstall(true);
            $configuration->setDevMode(true);

            $configuration->writeConfiguration();

            $text .= '<info>Install mode has been changed to true</info>'.PHP_EOL;
        }
        if ($input->getOption('disable-install')) {

            $configuration->setInstall(false);
            $configuration->writeConfiguration();

            $text .= '<info>Install mode has been changed to false</info>'.PHP_EOL;
            $text .= 'Do not forget to empty all cache and purge XCache/APC caches manually.'.PHP_EOL;
        }


        $output->writeln($text);
    }
}
