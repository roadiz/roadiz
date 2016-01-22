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
 * @file UsersPasswordCommand.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Console;

use RZ\Roadiz\Console\UsersCommand;
use RZ\Roadiz\Utils\Security\PasswordGenerator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

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
        $this->questionHelper = $this->getHelperSet()->get('question');
        $this->entityManager = $this->getHelperSet()->get('em')->getEntityManager();
        $text = "";
        $name = $input->getArgument('username');

        if ($name) {
            $user = $this->entityManager
                ->getRepository('RZ\Roadiz\Core\Entities\User')
                ->findOneBy(['username' => $name]);

            if (null !== $user) {
                $confirmation = new ConfirmationQuestion(
                    '<question>Do you really want to regenerate user “' . $user->getUsername() . '” password?</question> [y/N]: ',
                    false
                );
                if ($this->questionHelper->ask(
                    $input,
                    $output,
                    $confirmation
                )) {
                    $passwordGenerator = new PasswordGenerator();
                    $user->setPlainPassword($passwordGenerator->generatePassword(12));
                    $user->getHandler()->encodePassword();
                    $this->entityManager->flush();
                    $text = '<info>User password regenerated…</info>' . PHP_EOL;
                    $text .= 'Password: <info>' . $user->getPlainPassword() . '</info>' . PHP_EOL;

                } else {
                    $text = '<info>[Cancelled]</info> User password was not changed.' . PHP_EOL;
                }
            } else {
                $text = PHP_EOL . '<error>User “' . $name . '” does not exist.</error>' . PHP_EOL;
            }
        }

        $output->writeln($text);
    }
}
