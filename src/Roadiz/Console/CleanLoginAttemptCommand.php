<?php
declare(strict_types=1);

namespace RZ\Roadiz\Console;

use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\Core\Entities\LoginAttempt;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class CleanLoginAttemptCommand
 *
 * @package RZ\Roadiz\Console
 */
class CleanLoginAttemptCommand extends Command
{
    protected function configure()
    {
        $this->setName('login-attempts:clean')
            ->setDescription('Clean all login attempts older than 1 day');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->getHelper('doctrine')->getEntityManager();

        $entityManager->getRepository(LoginAttempt::class)
            ->cleanLoginAttempts();

        $io->success('All login attempts older than 1 day were deleted.');

        return 0;
    }
}
