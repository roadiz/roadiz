<?php
declare(strict_types=1);

namespace Themes\DefaultTheme\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * This is an example for defining custom CLI command.
 *
 * Add this class FQN to your conf/config.yml in the additionalCommands section
 * to see it appear in your commands menu.
 */
class DefaultThemeCommand extends Command
{
    protected function configure()
    {
        $this->setName('test-command')
            ->setDescription('This is a custom command defined from <info>DefaultTheme</info>.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->note('This is a custom example command defined from DefaultTheme.');
        return 0;
    }
}
