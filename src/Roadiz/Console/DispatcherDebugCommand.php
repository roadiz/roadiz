<?php
declare(strict_types=1);
/**
 * Copyright © 2016, Ambroise Maupate and Julien Blanchet
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
 * @file DispatcherDebugCommand.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Console;

use RZ\Roadiz\Core\Kernel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class DispatcherDebugCommand
 * @package RZ\Roadiz\Console
 */
class DispatcherDebugCommand extends Command implements ThemeAwareCommandInterface
{
    protected function configure()
    {
        $this
            ->setName('debug:event-dispatcher')
            ->setDefinition([
                new InputArgument('event', InputArgument::OPTIONAL, 'An event name'),
            ])
            ->setDescription('Displays configured listeners for an application')
            ->setHelp(
                <<<'EOF'
The <info>%command.name%</info> command displays all configured listeners:
  <info>php %command.full_name%</info>
To get specific listeners for an event, specify its name:
  <info>php %command.full_name% kernel.request</info>
EOF
            )
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
        $io = new SymfonyStyle($input, $output);
        $tableContent = [];
        /** @var EventDispatcher $dispatcher */
        $dispatcher = $kernel->get('dispatcher');

        foreach ($dispatcher->getListeners() as $eventName => $listeners) {
            /** @var EventSubscriberInterface $listener */
            foreach ($listeners as $priority => $listener) {
                if ($listener instanceof \Closure) {
                    $listenerClass = '\Closure';
                    $listenerMethod = '…';
                } else {
                    $listenerClass = get_class($listener[0]);
                    $listenerMethod = $listener[1];
                }
                $tableContent[] = [
                    $eventName,
                    $listenerClass,
                    $listenerMethod,
                    $priority,
                ];
            }
        }

        $io->table(['Event name', 'Listener', 'Method', 'Priority'], $tableContent);

        return 0;
    }
}
