<?php
declare(strict_types=1);

namespace RZ\Roadiz\Console;

use RZ\Roadiz\Core\Entities\User;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command line utils for managing users from terminal.
 */
class UsersEnableCommand extends UsersCommand
{
    protected function configure()
    {
        $this->setName('users:enable')
            ->setDescription('Enable a user')
            ->addArgument(
                'username',
                InputArgument::REQUIRED,
                'Username'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $this->entityManager = $this->getHelper('doctrine')->getEntityManager();
        $name = $input->getArgument('username');

        if ($name) {
            $user = $this->entityManager
                ->getRepository(User::class)
                ->findOneBy(['username' => $name]);

            if (null !== $user) {
                $confirmation = new ConfirmationQuestion(
                    '<question>Do you really want to enable user “' . $user->getUsername() . '”?</question>',
                    false
                );
                if (!$input->isInteractive() || $io->askQuestion(
                    $confirmation
                )) {
                    $user->setEnabled(true);
                    $this->entityManager->flush();
                    $io->success('User “' . $name . '” was enabled.');
                } else {
                    $io->warning('User “' . $name . '” was not enabled');
                }
            } else {
                throw new \InvalidArgumentException('User “' . $name . '” does not exist.');
            }
        }
        return 0;
    }
}
