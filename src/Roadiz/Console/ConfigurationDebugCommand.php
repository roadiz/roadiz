<?php
/**
 * Copyright (c) 2018. Ambroise Maupate and Julien Blanchet
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
 * @file ConfigurationDebugCommand.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */
namespace RZ\Roadiz\Console;

use RZ\Roadiz\Core\Kernel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class DispatcherDebugCommand
 * @package RZ\Roadiz\Console
 */
class ConfigurationDebugCommand extends Command implements ThemeAwareCommandInterface
{
    protected function configure()
    {
        $this
            ->setName('debug:configuration')
            ->setDescription('Displays current Roadiz application configuration')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Kernel $kernel */
        $kernel = $this->getHelper('kernel')->getKernel();

        $configuration = $kernel->get('config');

        $table = new Table($output);
        $table->setHeaders(['Configuration path', 'Value']);
        $tableContent = [];

        foreach ($configuration as $key => $value) {
            $tableContent = array_merge($tableContent, $this->dumpConfiguration($key, $value));
        }

        $table->setRows($tableContent);
        $table->render();

        return 0;
    }

    /**
     * @param $key
     * @param $value
     *
     * @return array
     */
    protected function dumpConfiguration($key, $value)
    {
        if (is_array($value)) {
            $subConfig = [];
            foreach ($value as $subKey => $subValue) {
                $subConfig = array_merge($subConfig, $this->dumpConfiguration(
                    $key.'.'.$subKey,
                    $subValue
                ));
            }
            return $subConfig;
        } elseif (is_null($value)) {
            return [
                [$key, '<info>null</info>']
            ];
        } elseif (is_bool($value)) {
            return [
                [$key, ($value ? '<info>true</info>' : '<info>false</info>')]
            ];
        } else {
            return [
                [$key, $value]
            ];
        }
    }
}
