<?php
/**
 * Copyright © 2014, Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file UsersCommand.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Console;

use RZ\Roadiz\Core\Bags\RolesBag;
use RZ\Roadiz\Core\Entities\Role;
use RZ\Roadiz\Core\Entities\User;
use RZ\Roadiz\Utils\Security\PasswordGenerator;
use RZ\Roadiz\Utils\MediaFinders\FacebookPictureFinder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Command line utils for managing users from terminal.
 */
class UsersCommand extends Command
{
    private $questionHelper;
    private $entityManager;

    protected function configure()
    {
        $this->setName('core:users')
             ->setDescription('Manage users')
             ->addArgument(
                 'username',
                 InputArgument::OPTIONAL,
                 'User name'
             )
             ->addOption(
                 'create',
                 'c',
                 InputOption::VALUE_NONE,
                 'Create a new user'
             )
             ->addOption(
                 'delete',
                 'D',
                 InputOption::VALUE_NONE,
                 'Delete an user'
             )
             ->addOption(
                 'add-roles',
                 'R',
                 InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                 'Add roles to a user'
             )
             ->addOption(
                 'regenerate',
                 null,
                 InputOption::VALUE_NONE,
                 'Regenerate user’s password'
             )
             ->addOption(
                 'picture',
                 null,
                 InputOption::VALUE_NONE,
                 'Try to grab user picture from facebook'
             )
             ->addOption(
                 'disable',
                 'd',
                 InputOption::VALUE_NONE,
                 'Disable user'
             )
             ->addOption(
                 'enable',
                 'E',
                 InputOption::VALUE_NONE,
                 'Enable user'
             );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->questionHelper = $this->getHelperSet()->get('question');
        $this->entityManager = $this->getHelperSet()->get('em')->getEntityManager();
        $text = "";
        $name = $input->getArgument('username');

        if ($name) {
            $user = $this->entityManager
                         ->getRepository('RZ\Roadiz\Core\Entities\User')
                         ->findOneBy(['username' => $name]);

            if ($user !== null) {
                if ($input->getOption('enable')) {
                    if ($user !== null && $user->setEnabled(true)) {
                        $this->entityManager->flush();
                        $text = '<info>User enabled…</info>' . PHP_EOL;
                    } else {
                        $text = '<error>Requested user is not setup yet…</error>' . PHP_EOL;
                    }
                } elseif ($input->getOption('disable')) {
                    if ($user !== null && $user->setEnabled(false)) {
                        $this->entityManager->flush();
                        $text = '<info>User disabled…</info>' . PHP_EOL;
                    } else {
                        $text = '<error>Requested user is not setup yet…</error>' . PHP_EOL;
                    }
                } elseif ($input->getOption('delete')) {
                    $confirmation = new ConfirmationQuestion(
                        '<question>Do you really want to delete user “' . $user->getUsername() . '”?</question>',
                        false
                    );
                    if ($user !== null && $this->questionHelper->ask(
                        $input,
                        $output,
                        $confirmation
                    )) {
                        $this->entityManager->remove($user);
                        $this->entityManager->flush();
                        $text = '<info>User deleted…</info>' . PHP_EOL;
                    } else {
                        $text = '<error>Requested user is not setup yet…</error>' . PHP_EOL;
                    }
                } elseif ($input->getOption('picture')) {
                    if ($user !== null) {
                        $facebook = new FacebookPictureFinder($user->getFacebookName());
                        if (false !== $url = $facebook->getPictureUrl()) {
                            $user->setPictureUrl($url);
                            $this->entityManager->flush();
                            $text = '<info>User profile pciture updated…</info>' . PHP_EOL;
                        }
                    } else {
                        $text = '<error>Requested user is not setup yet…</error>' . PHP_EOL;
                    }
                } elseif ($input->getOption('regenerate')) {
                    if ($user !== null && $this->questionHelper->askConfirmation(
                        $output,
                        '<question>Do you really want to regenerate user “' . $user->getUsername() . '” password?</question> : ',
                        false
                    )) {
                        $passwordGenerator = new PasswordGenerator();
                        $user->setPlainPassword($passwordGenerator->generatePassword(12));
                        $user->getHandler()->encodePassword();

                        $this->entityManager->flush();
                        $text = '<info>User password regenerated…</info>' . PHP_EOL;
                        $text .= 'Password: <info>' . $user->getPlainPassword() . '</info>' . PHP_EOL;

                    } else {
                        $text = '<error>Requested user is not setup yet…</error>' . PHP_EOL;
                    }
                } elseif ($input->getOption('add-roles') && $user !== null) {
                    $text = '<info>Adding roles to ' . $user->getUsername() . '</info>' . PHP_EOL;

                    foreach ($input->getOption('add-roles') as $role) {
                        $user->addRole(RolesBag::get($role));
                        $text .= '<info>Role: ' . $role . '</info>' . PHP_EOL;
                    }

                    $this->entityManager->flush();
                } else {
                    $text = '<info>' . $user . '</info>' . PHP_EOL;
                }
            } else {
                if ($input->getOption('create')) {
                    $this->executeUserCreation($name, $input, $output);
                } else {
                    $text = '<error>User “' . $name . '” does not exist… use --create to add a new user.</error>' . PHP_EOL;
                }
            }
        } else {
            $text = '<info>Installed users…</info>' . PHP_EOL;
            $users = $this->entityManager
                          ->getRepository('RZ\Roadiz\Core\Entities\User')
                          ->findAll();

            if (count($users) > 0) {
                $text .= ' | ' . PHP_EOL;
                foreach ($users as $user) {
                    $text .=
                    ' |_ ' . $user->getUsername()
                    . ' — <info>' . ($user->isEnabled() ? 'enabled' : 'disabled') . '</info>'
                    . ' — <comment>' . implode(', ', $user->getRoles()) . '</comment>'
                    . PHP_EOL;
                }
            } else {
                $text = '<info>No available users</info>' . PHP_EOL;
            }
        }

        $output->writeln($text);
    }

    /**
     * @param string          $username
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return RZ\Roadiz\Core\Entities\User
     */
    private function executeUserCreation(
        $username,
        InputInterface $input,
        OutputInterface $output
    ) {
        $user = new User();
        $user->setUsername($username);

        do {
            $questionEmail = new Question(
                '<question>Email</question> : ',
                ''
            );
            $email = $this->questionHelper->ask(
                $input,
                $output,
                $questionEmail
            );
        } while (!filter_var($email, FILTER_VALIDATE_EMAIL) ||
            $this->entityManager->getRepository('RZ\Roadiz\Core\Entities\User')->emailExists($email)
        );

        $user->setEmail($email);

        $questionBack = new ConfirmationQuestion(
            '<question>Is user a backend user?</question> : ',
            false
        );
        if ($this->questionHelper->ask(
            $input,
            $output,
            $questionBack
        )) {
            $user->addRole($this->getRole(Role::ROLE_BACKEND_USER));
        }

        $questionAdmin = new ConfirmationQuestion(
            '<question>Is user a super-admin user?</question> : ',
            false
        );
        if ($this->questionHelper->ask(
            $input,
            $output,
            $questionAdmin
        )) {
            $user->addRole($this->getRole(Role::ROLE_SUPERADMIN));
        }

        $this->entityManager->persist($user);
        $user->getViewer()->sendSignInConfirmation();
        $this->entityManager->flush();

        $text = '<info>User “' . $username . '”<' . $email . '> created…</info>' . PHP_EOL;
        $text .= '<info>Password “' . $user->getPlainPassword() . '”.</info>' . PHP_EOL;
        $output->writeln($text);

        return $user;
    }

    /**
     * Get role by name, and create it if does not exist.
     *
     * @param string $roleName
     *
     * @return Role
     */
    public function getRole($roleName = Role::ROLE_SUPERADMIN)
    {
        $role = $this->entityManager
                     ->getRepository('RZ\Roadiz\Core\Entities\Role')
                     ->findOneBy(['name' => $roleName]);

        if ($role === null) {
            $role = new Role($roleName);
            $this->entityManager->persist($role);
            $this->entityManager->flush();
        }

        return $role;
    }
}
