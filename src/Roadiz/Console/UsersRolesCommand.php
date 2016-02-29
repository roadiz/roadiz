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
 * @file UsersRolesCommand.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Console;


use RZ\Roadiz\Core\Bags\RolesBag;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

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
        $this->questionHelper = $this->getHelperSet()->get('question');
        $this->entityManager = $this->getHelperSet()->get('em')->getEntityManager();
        $text = "";
        $name = $input->getArgument('username');

        if ($name) {
            $user = $this->entityManager
                ->getRepository('RZ\Roadiz\Core\Entities\User')
                ->findOneBy(['username' => $name]);

            if (null !== $user) {
                if ($input->getOption('add')) {
                    $roles = $this->entityManager
                        ->getRepository('RZ\Roadiz\Core\Entities\Role')
                        ->getAllRoleName();

                    $question = new Question(
                        'Enter the role name to add (empty to stop): ',
                        ''
                    );
                    $question->setAutocompleterValues($roles);

                    do {
                        $role = $this->questionHelper->ask($input, $output, $question);
                        if (in_array($role, $roles)) {
                            $user->addRole(RolesBag::get($role));
                            $text .= '<info>— Role: ' . $role . ' added.</info>' . PHP_EOL;
                        }
                    } while ($role != "");

                    $this->entityManager->flush();
                } elseif ($input->getOption('remove')) {
                    do {
                        $roles = $user->getRoles();
                        $question = new Question(
                            'Enter the role name to remove (empty to stop): ',
                            ''
                        );
                        $question->setAutocompleterValues($roles);

                        $role = $this->questionHelper->ask($input, $output, $question);
                        if (in_array($role, $roles)) {
                            $user->removeRole(RolesBag::get($role));
                            $text .= '<info>— Role: ' . $role . ' removed.</info>' . PHP_EOL;
                        }
                    } while ($role != "");
                    $this->entityManager->flush();
                } else {
                    $text = '<info>' . $user . '</info>' . PHP_EOL;
                }
            } else {
                $text = PHP_EOL . '<error>User “' . $name . '” does not exist.</error>' . PHP_EOL;
            }
        }

        $output->writeln($text);
    }
}
