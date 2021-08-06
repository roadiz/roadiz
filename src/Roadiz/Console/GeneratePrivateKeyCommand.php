<?php
declare(strict_types=1);

namespace RZ\Roadiz\Console;

use RZ\Crypto\KeyChain\KeyChainInterface;
use RZ\Roadiz\Core\Kernel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @package RZ\Roadiz\Console
 */
class GeneratePrivateKeyCommand extends Command
{
    protected function configure()
    {
        $this->setName('generate:private-key')
            ->setDescription('Generate a default private key to encode data in your database.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        /** @var Kernel $kernel */
        $kernel = $this->getHelper('kernel')->getKernel();
        $privateKeyPath = $kernel->get('crypto.absolute_private_key_path');

        if (file_exists($privateKeyPath)) {
            $io->note(sprintf('A private key already exists at %s.', $privateKeyPath));
        } else {
            $filename = pathinfo($privateKeyPath, PATHINFO_FILENAME);
            $kernel->get(KeyChainInterface::class)->generate($filename);
            $io->success(sprintf('Private key has been generated in %s', $privateKeyPath));
        }
        return 0;
    }
}
