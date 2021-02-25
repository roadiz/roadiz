<?php
declare(strict_types=1);

namespace RZ\Roadiz\Console;

use RZ\Roadiz\Core\Entities\User;
use RZ\Roadiz\Random\PasswordGenerator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command line utils for managing users from terminal.
 */
class UsersPasswordCommand extends UsersCommand
{
    protected function configure()
    {
        $this->setName('users:password')
            ->setDescription('Regenerate a new password for user')
            ->addArgument(
                'username',
                InputArgument::REQUIRED,
                'Username'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $this->entityManager = $this->getHelper('entityManager')->getEntityManager();
        $name = $input->getArgument('username');

        if ($name) {
            /** @var User|null $user */
            $user = $this->entityManager
                ->getRepository(User::class)
                ->findOneBy(['username' => $name]);

            if (null !== $user) {
                $confirmation = new ConfirmationQuestion(
                    '<question>Do you really want to regenerate user “' . $user->getUsername() . '” password?</question>',
                    false
                );
                if (!$input->isInteractive() || $io->askQuestion(
                    $confirmation
                )) {
                    $passwordGenerator = new PasswordGenerator();
                    $user->setPlainPassword($passwordGenerator->generatePassword(12));
                    $this->entityManager->flush();
                    $io->success('A new password was regenerated for '.$name.': '. $user->getPlainPassword());
                } else {
                    $io->warning('User password was not changed.');
                }
            } else {
                throw new \InvalidArgumentException('User “' . $name . '” does not exist.');
            }
        }
        return 0;
    }
}
