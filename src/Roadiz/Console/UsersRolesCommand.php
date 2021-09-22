<?php
declare(strict_types=1);

namespace RZ\Roadiz\Console;

use RZ\Roadiz\Core\Entities\Role;
use RZ\Roadiz\Core\Entities\User;
use RZ\Roadiz\Utils\Console\Helper\RolesBagHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command line utils for managing users from terminal.
 */
class UsersRolesCommand extends UsersCommand
{
    protected function configure()
    {
        $this->setName('users:roles')
            ->setDescription('Manage user roles')
            ->addArgument(
                'username',
                InputArgument::REQUIRED,
                'Username'
            )
            ->addOption(
                'add',
                'a',
                InputOption::VALUE_NONE,
                'Add roles to a user'
            )
            ->addOption(
                'remove',
                'r',
                InputOption::VALUE_NONE,
                'Remove roles from a user'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        /** @var RolesBagHelper $rolesBag */
        $rolesBag = $this->getHelper('rolesBag');
        $this->entityManager = $this->getHelper('doctrine')->getEntityManager();
        $name = $input->getArgument('username');

        if ($name) {
            /** @var User|null $user */
            $user = $this->entityManager
                ->getRepository(User::class)
                ->findOneBy(['username' => $name]);

            if (null !== $user) {
                if ($input->getOption('add')) {
                    $roles = $this->entityManager
                        ->getRepository(Role::class)
                        ->getAllRoleName();

                    $question = new Question(
                        'Enter the role name to add'
                    );
                    $question->setAutocompleterValues($roles);

                    do {
                        $role = $io->askQuestion($question);
                        if ($role != "") {
                            $user->addRole($rolesBag->get($role));
                            $this->entityManager->flush();
                            $io->success('Role: ' . $role . ' added.');
                        }
                    } while ($role != "");
                } elseif ($input->getOption('remove')) {
                    do {
                        $roles = $user->getRoles();
                        $question = new Question(
                            'Enter the role name to remove'
                        );
                        $question->setAutocompleterValues($roles);

                        $role = $io->askQuestion($question);
                        if (in_array($role, $roles)) {
                            $user->removeRole($rolesBag->get($role));
                            $this->entityManager->flush();
                            $io->success('Role: ' . $role . ' removed.');
                        }
                    } while ($role != "");
                }
            } else {
                throw new \InvalidArgumentException('User “' . $name . '” does not exist.');
            }
        }
        return 0;
    }
}
