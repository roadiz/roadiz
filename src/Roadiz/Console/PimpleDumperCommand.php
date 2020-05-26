<?php
declare(strict_types=1);

namespace RZ\Roadiz\Console;

use JBZoo\PimpleDumper\PimpleDumper;
use RZ\Roadiz\Core\Kernel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Pimple dumper command to generate a pimple.json for IDE autocompletion plugins.
 */
class PimpleDumperCommand extends Command implements ThemeAwareCommandInterface
{
    protected function configure()
    {
        $this
            ->setName('debug:pimple')
            ->setDescription('Dump all Pimple services into a pimple.json file.')
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
        $dumper = new PimpleDumper();
        if ($kernel instanceof Kernel) {
            $dumper->setRoot($kernel->getProjectDir());
            $dumper->dumpPimple($kernel->getContainer(), true); // Append to current pimple.json
            $io->success(sprintf('Pimple container was dumped into %s file.', $kernel->getProjectDir() . '/pimple.json'));
            return 0;
        }

        $io->error('No configuration to dump.');
        return 1;
    }
}
