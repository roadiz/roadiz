<?php
declare(strict_types=1);

namespace RZ\Roadiz\Console;

use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\Core\Entities\LoginAttempt;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class PurgeLoginAttemptCommand
 *
 * @package RZ\Roadiz\Console
 */
class PurgeLoginAttemptCommand extends Command
{
    protected function configure()
    {
        $this->setName('login-attempts:purge')
            ->setDescription('Purge all login attempts for one IP address')
            ->addArgument(
                'ip-address',
                InputArgument::REQUIRED
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->getHelper('doctrine')->getEntityManager();

        $entityManager->getRepository(LoginAttempt::class)
            ->purgeLoginAttempts($input->getArgument('ip-address'));

        $io->success('All login attempts were deleted for ' . $input->getArgument('ip-address'));

        return 0;
    }
}
