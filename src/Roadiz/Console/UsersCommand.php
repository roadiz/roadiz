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

use RZ\Roadiz\Core\Entities\Role;
use RZ\Roadiz\Core\Entities\User;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command line utils for managing users from terminal.
 */
class UsersCommand extends Command
{
    protected $entityManager;

    protected function configure()
    {
        $this->setName('users:list')
            ->setDescription('List all users or just one')
            ->addArgument(
                'username',
                InputArgument::OPTIONAL,
                'User name'
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

            if ($user === null) {
                $io->error('User “' . $name . '” does not exist… use users:create to add a new user.');
            } else {
                $tableContent = [[
                    $user->getId(),
                    $user->getUsername(),
                    $user->getEmail(),
                    (!$user->isEnabled() ? 'X' : ''),
                    ($user->getExpired() ? 'X' : ''),
                    (!$user->isAccountNonLocked() ? 'X' : ''),
                    implode(' ', $user->getGroupNames()),
                    implode(' ', $user->getRoles()),
                ]];
                $io->table(
                    ['Id', 'Username', 'Email', 'Disabled', 'Expired', 'Locked', 'Groups', 'Roles'],
                    $tableContent
                );
            }
        } else {
            $users = $this->entityManager
                ->getRepository(User::class)
                ->findAll();

            if (count($users) > 0) {
                $tableContent = [];
                foreach ($users as $user) {
                    $tableContent[] = [
                        $user->getId(),
                        $user->getUsername(),
                        $user->getEmail(),
                        (!$user->isEnabled() ? 'X' : ''),
                        ($user->getExpired() ? 'X' : ''),
                        (!$user->isAccountNonLocked() ? 'X' : ''),
                        implode(' ', $user->getGroupNames()),
                        implode(' ', $user->getRoles()),
                    ];
                }

                $io->table(
                    ['Id', 'Username', 'Email', 'Disabled', 'Expired', 'Locked', 'Groups', 'Roles'],
                    $tableContent
                );
            } else {
                $io->warning('No available users.');
            }
        }
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
            ->getRepository(Role::class)
            ->findOneBy(['name' => $roleName]);

        if ($role === null) {
            $role = new Role($roleName);
            $this->entityManager->persist($role);
            $this->entityManager->flush();
        }

        return $role;
    }
}
