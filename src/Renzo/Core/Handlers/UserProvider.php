<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file UserProvider.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Handlers;

use RZ\Renzo\Core\Entities\User;
use RZ\Renzo\Core\Kernel;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
/**
 * {@inheritdoc}
 */
class UserProvider implements UserProviderInterface
{
    /**
     * Loads the user for the given username.
     *
     * This method must throw UsernameNotFoundException if the user is not
     * found.
     *
     * @param string $username The username
     *
     * @return RZ\Renzo\Core\Entities\User
     * @see UsernameNotFoundException
     * @throws UsernameNotFoundException if the user is not found
     */
    public function loadUserByUsername($username)
    {
        $user = Kernel::getInstance()->em()
            ->getRepository('RZ\Renzo\Core\Entities\User')
            ->findOneBy(array('username' => $username));

        if ($user !== null) {
            return $user;
        } else {
            throw new UsernameNotFoundException();
        }
    }

    /**
     * Refreshes the user for the account interface.
     *
     * It is up to the implementation to decide if the user data should be
     * totally reloaded (e.g. from the database), or if the RZ\Renzo\Core\Entities\User
     * object can just be merged into some internal array of users / identity
     * map.
     *
     * @param RZ\Renzo\Core\Entities\User $user
     *
     * @return RZ\Renzo\Core\Entities\User
     * @throws UnsupportedUserException if the account is not supported
     */
    public function refreshUser(UserInterface $user)
    {
        $refreshUser = Kernel::getInstance()->em()
            ->find('RZ\Renzo\Core\Entities\User', (int) $user->getId());

        if ($refreshUser !== null) {
            return $refreshUser;
        } else {
            throw new UnsupportedUserException();
        }
    }
    /**
     * Whether this provider supports the given user class
     *
     * @param string $class
     *
     * @return bool
     */
    public function supportsClass($class)
    {
        if ($class == "RZ\Renzo\Core\Entities\User") {
            return true;
        }

        return false;
    }
}
