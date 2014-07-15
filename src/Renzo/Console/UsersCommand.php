<?php 

namespace RZ\Renzo\Console;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\User;
use RZ\Renzo\Core\Entities\Role;
use RZ\Renzo\Core\Handlers\UserHandler;
use RZ\Renzo\Core\Bags\RolesBag;
use RZ\Renzo\Core\Utils\FacebookPictureFinder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class UsersCommand extends Command
{
    private $dialog;
	
	protected function configure()
    {
        $this
            ->setName('users')
            ->setDescription('Manage users')
            ->addArgument(
                'username',
                InputArgument::OPTIONAL,
                'User name'
            )
            ->addOption(
               'create',
               null,
               InputOption::VALUE_NONE,
               'Create a new user'
            )
            ->addOption(
               'delete',
               null,
               InputOption::VALUE_NONE,
               'Delete an user'
            )
            ->addOption(
               'add-roles',
               null,
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
               null,
               InputOption::VALUE_NONE,
               'Disable user'
            )
            ->addOption(
               'enable',
               null,
               InputOption::VALUE_NONE,
               'Enable user'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $this->dialog = $this->getHelperSet()->get('dialog');
        $text="";
        $name = $input->getArgument('username');

        if ($name) {
            $user = Kernel::getInstance()->em()
                ->getRepository('RZ\Renzo\Core\Entities\User')
                ->findOneBy(array('username'=>$name));

            if ($user !== null) {

                if ($input->getOption('enable')) {

                    if ($user !== null && $user->setEnabled(true)) {
                        Kernel::getInstance()->em()->flush();
                        $text = '<info>User enabled…</info>'.PHP_EOL;
                    }
                    else {
                        $text = '<error>Requested user is not setup yet…</error>'.PHP_EOL;
                    }
                }
                elseif ($input->getOption('disable')) {

                    if ($user !== null && $user->setEnabled(false)) {
                        Kernel::getInstance()->em()->flush();
                        $text = '<info>User disabled…</info>'.PHP_EOL;
                    }
                    else {
                        $text = '<error>Requested user is not setup yet…</error>'.PHP_EOL;
                    }
                }
                elseif ($input->getOption('delete')) {

                    if ($user !== null && $this->dialog->askConfirmation(
                            $output,
                            '<question>Do you really want to delete user “'.$user->getUsername().'”?</question> : ',
                            false
                        )) {
                        Kernel::getInstance()->em()->remove($user);
                        Kernel::getInstance()->em()->flush();
                        $text = '<info>User deleted…</info>'.PHP_EOL;
                    }
                    else {
                        $text = '<error>Requested user is not setup yet…</error>'.PHP_EOL;
                    }
                }
                elseif ($input->getOption('picture')) {
                    if ($user !== null ) {
                        
                        $facebook = new FacebookPictureFinder($user->getFacebookName());
                        if (false !== $url = $facebook->getPictureUrl()) {
                            $user->setPictureUrl($url);
                            Kernel::getInstance()->em()->flush();
                            $text = '<info>User profile pciture updated…</info>'.PHP_EOL;
                        }
                    }
                    else {
                        $text = '<error>Requested user is not setup yet…</error>'.PHP_EOL;
                    }
                }
                elseif($input->getOption('regenerate')){
                    if ($user !== null && $this->dialog->askConfirmation(
                            $output,
                            '<question>Do you really want to regenerate user “'.$user->getUsername().'” password?</question> : ',
                            false
                        )) {

                        $user->setPlainPassword( UserHandler::generatePassword() );

                        Kernel::getInstance()->em()->flush();
                        $text = '<info>User password regenerated…</info>'.PHP_EOL;
                        $text .= '<info>Password “'.$user->getPlainPassword().'”.</info>'.PHP_EOL;
                    }
                    else {
                        $text = '<error>Requested user is not setup yet…</error>'.PHP_EOL;
                    }
                }
                elseif ($input->getOption('add-roles') && $user !== null) {
                    $text = '<info>Adding roles to '.$user->getUsername().'</info>'.PHP_EOL;

                    foreach ($input->getOption('add-roles') as $role) {
                        $user->addRole(RolesBag::get($role));
                        $text .= '<info>Role: '.$role.'</info>'.PHP_EOL;
                    }

                    Kernel::getInstance()->em()->flush();
                }
                else {
                    $text = '<info>'.$user.'</info>'.PHP_EOL;
                }
            }
            else {
                if ($input->getOption('create')) {
                    $this->executeUserCreation( $name, $input, $output);
                }
                else {
                    $text = '<error>User “'.$name.'” does not exist… use --create to add a new user.</error>'.PHP_EOL;
                }
            }
        } 
        else {
            $text = '<info>Installed users…</info>'.PHP_EOL;
            $users = Kernel::getInstance()->em()
                ->getRepository('RZ\Renzo\Core\Entities\User')
                ->findAll();

            if (count($users) > 0) {
                $text .= ' | '.PHP_EOL;
                foreach ( $users as $user) {

                    $text .= 
                        ' |_ '.$user->getUsername()
                        .' — <info>'.($user->isEnabled()?'enabled':'disabled').'</info>'
                        .' — <comment>'.implode(', ', $user->getRoles()).'</comment>'
                        .PHP_EOL;
                }
            }
            else {
                $text = '<info>No available users</info>'.PHP_EOL;
            }
        }

        $output->writeln($text);
    }

    /**
     * [executeUserCreation description]
     * @param  string  $username       [description]
     * @param  InputInterface  $input       [description]
     * @param  OutputInterface $output      [description]
     * @return RZ\Renzo\Core\Entities\User
     */
    private function executeUserCreation( $username,
                                          InputInterface $input, 
                                          OutputInterface $output ) {

        
        $text = "";
        $user = new User( );
        $user->setUsername($username);

        $email = false;

        do {
            $email = $this->dialog->ask(
                $output,
                '<question>Email</question> : ',
                ''
            );
        } while (
            !filter_var($email, FILTER_VALIDATE_EMAIL) || 
            Kernel::getInstance()->em()->getRepository('RZ\Renzo\Core\Entities\User')->emailExists($email)
        );

        $user->setEmail($email);

        if ($this->dialog->askConfirmation(
                $output,
                '<question>Is user a backend user?</question> : ',
                false
            )) {
                
            $user->addRole( $this->getRole(Role::ROLE_BACKEND_USER) );
        }
        if ($this->dialog->askConfirmation(
                $output,
                '<question>Is user a super-admin user?</question> : ',
                false
            )) {
        
            $user->addRole( $this->getRole(Role::ROLE_SUPER_ADMIN) );
        }

        $user->setPlainPassword( UserHandler::generatePassword() );

        Kernel::getInstance()->em()->persist($user);
        $user->getViewer()->sendSignInConfirmation();
        Kernel::getInstance()->em()->flush();

        $text = '<info>User “'.$username.'”<'.$email.'> created…</info>'.PHP_EOL;
        $text .= '<info>Password “'.$user->getPlainPassword().'”.</info>'.PHP_EOL;
        $output->writeln($text);

        return $user;
    }

    /**
     * Get role by name, and create it if does not exist
     * @param  string $roleName
     * @return Role
     */
    public function getRole( $roleName = Role::ROLE_SUPER_ADMIN )
    {
        $role = Kernel::getInstance()->em()
                ->getRepository('RZ\Renzo\Core\Entities\Role')
                ->findOneBy(array('name'=>$roleName));

        if ($role === null) {
            $role = new Role($roleName);
            Kernel::getInstance()->em()->persist($role);
            Kernel::getInstance()->em()->flush();
        }

        return $role;
    }
}