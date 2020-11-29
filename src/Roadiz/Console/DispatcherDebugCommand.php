<?php
declare(strict_types=1);

namespace RZ\Roadiz\Console;

use RZ\Roadiz\Core\Kernel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcher;

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
