<?php
/**
 * Copyright © 2016, Ambroise Maupate and Julien Blanchet
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
 * @file UsersCreationCommand.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Console;

use RZ\Roadiz\Core\Entities\Role;
use RZ\Roadiz\Core\Entities\User;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * Command line utils for managing users from terminal.
 */
class UsersCreationCommand extends UsersCommand
{
    protected function configure()
    {
        $this->setName('users:create')
            ->setDescription('Create a user. Without <info>--password</info> a random password will be generated and sent by email. <info>Check if "email_sender" setting is valid.</info>')
            ->addOption('email', 'm', InputOption::VALUE_REQUIRED, 'Set user email.')
            ->addOption('password', 'p', InputOption::VALUE_REQUIRED, 'Set user password (typing plain password in command-line is insecure).')
            ->addOption('back-end', 'b', InputOption::VALUE_NONE, 'Add ROLE_BACKEND_USER to user.')
            ->addOption('super-admin', 's', InputOption::VALUE_NONE, 'Add ROLE_SUPERADMIN to user.')
            ->addUsage('--email=test@test.com --password=secret --back-end --super-admin test')
            ->addArgument(
                'username',
                InputArgument::REQUIRED,
                'Username'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->questionHelper = $this->getHelper('question');
        $this->entityManager = $this->getHelper('entityManager')->getEntityManager();
        $text = "";
        $name = $input->getArgument('username');

        if ($name) {
            $user = $this->entityManager
                ->getRepository('RZ\Roadiz\Core\Entities\User')
                ->findOneBy(['username' => $name]);

            if (null === $user) {
                $this->executeUserCreation($name, $input, $output);
            } else {
                throw new \InvalidArgumentException('User “' . $name . '” already exists.');
            }
        }

        $output->writeln($text);
    }

    /**
     * @param string          $username
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return \RZ\Roadiz\Core\Entities\User
     */
    private function executeUserCreation(
        $username,
        InputInterface $input,
        OutputInterface $output
    ) {
        $user = new User();
        if (!$input->hasOption('password')) {
            $user->sendCreationConfirmationEmail(true);
        }
        $user->setUsername($username);

        if ($input->isInteractive() && !$input->getOption('email')) {
            /*
             * Interactive
             */
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
            } while (
                !filter_var($email, FILTER_VALIDATE_EMAIL) ||
                $this->entityManager->getRepository('RZ\Roadiz\Core\Entities\User')->emailExists($email)
            );
        } else {
            /*
             * From CLI
             */
            $email = $input->getOption('email');
            if ($this->entityManager->getRepository('RZ\Roadiz\Core\Entities\User')->emailExists($email)) {
                throw new \InvalidArgumentException('Email already exists.');
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) ) {
                throw new \InvalidArgumentException('Email is not valid.');
            }
        }

        $user->setEmail($email);

        if ($input->isInteractive() && !$input->getOption('back-end')) {
            $questionBack = new ConfirmationQuestion(
                '<question>Is user a backend user?</question> [y/N]: ',
                false
            );
            if ($this->questionHelper->ask(
                $input,
                $output,
                $questionBack
            )) {
                $user->addRole($this->getRole(Role::ROLE_BACKEND_USER));
            }
        } elseif ($input->getOption('back-end') === true) {
            $user->addRole($this->getRole(Role::ROLE_BACKEND_USER));
        }

        if ($input->isInteractive() && !$input->getOption('super-admin')) {
            $questionAdmin = new ConfirmationQuestion(
                '<question>Is user a super-admin user?</question> [y/N]: ',
                false
            );
            if ($this->questionHelper->ask(
                $input,
                $output,
                $questionAdmin
            )) {
                $user->addRole($this->getRole(Role::ROLE_SUPERADMIN));
            }
        } elseif ($input->getOption('super-admin') === true) {
            $user->addRole($this->getRole(Role::ROLE_SUPERADMIN));
        }

        if ($input->getOption('password')) {
            if (strlen($input->getOption('password')) < 5) {
                throw new \InvalidArgumentException('Password is too short.');
            }

            $user->setPlainPassword($input->getOption('password'));
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $text = '<info>User “' . $username . '”<' . $email . '> created…</info>' . PHP_EOL;
        $text .= '<info>Password “' . $user->getPlainPassword() . '”.</info>' . PHP_EOL;
        $output->writeln($text);

        return $user;
    }
}
