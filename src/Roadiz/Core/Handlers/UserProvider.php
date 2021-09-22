<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Handlers;

use Doctrine\Persistence\ManagerRegistry;
use RZ\Roadiz\Core\Entities\User;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @package RZ\Roadiz\Core\Handlers
 */
class UserProvider implements UserProviderInterface
{
    protected ManagerRegistry $managerRegistry;

    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * Loads the user for the given username.
     *
     * This method must throw UsernameNotFoundException if the user is not
     * found.
     *
     * @param string $username The username
     *
     * @return User
     * @throws UsernameNotFoundException if the user is not found
     */
    public function loadUserByUsername($username)
    {
        /** @var User|null $user */
        $user = $this->managerRegistry
                     ->getRepository(User::class)
                     ->findOneBy(['username' => $username]);

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
     * totally reloaded (e.g. from the database), or if the RZ\Roadiz\Core\Entities\User
     * object can just be merged into some internal array of users / identity
     * map.
     *
     * @param UserInterface $user
     * @return User
     * @throws UnsupportedUserException
     */
    public function refreshUser(UserInterface $user)
    {
        if ($user instanceof User) {
            $manager = $this->managerRegistry->getManagerForClass(User::class);
            /** @var User|null $refreshUser */
            $refreshUser = $manager->find(User::class, (int) $user->getId());
            if ($refreshUser !== null &&
                $refreshUser->isEnabled() &&
                $refreshUser->isAccountNonExpired() &&
                $refreshUser->isAccountNonLocked()) {
                // Always refresh User from database: too much related entities to rely only on token.
                return $refreshUser;
            } else {
                throw new UsernameNotFoundException('Token user does not exist anymore, authenticate again…');
            }
        }
        throw new UnsupportedUserException();
    }
    /**
     * Whether this provider supports the given user class
     *
     * @param class-string $class
     *
     * @return bool
     */
    public function supportsClass($class)
    {
        return $class === User::class;
    }
}
