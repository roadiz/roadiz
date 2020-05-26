<?php
declare(strict_types=1);

namespace RZ\Roadiz\Console;

use RZ\Roadiz\Core\Kernel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

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
        $io = new SymfonyStyle($input, $output);
        $configuration = $kernel->get('config');
        $tableContent = [];

        foreach ($configuration as $key => $value) {
            $tableContent = array_merge($tableContent, $this->dumpConfiguration($key, $value));
        }
        $io->table(['Configuration path', 'Value'], $tableContent);

        return 0;
    }

    /**
     * @param string $key
     * @param mixed $value
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
