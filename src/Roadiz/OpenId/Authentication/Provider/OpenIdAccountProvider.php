<?php
declare(strict_types=1);

namespace RZ\Roadiz\OpenId\Authentication\Provider;

use RZ\Roadiz\OpenId\User\OpenIdAccount;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class OpenIdAccountProvider implements UserProviderInterface
{
    /**
     * @inheritDoc
     */
    public function loadUserByUsername($username)
    {
        throw new \RuntimeException('Cannot load an OpenId account with its email.');
    }

    /**
     * @inheritDoc
     */
    public function refreshUser(UserInterface $user)
    {
        return $user;
    }

    /**
     * @inheritDoc
     */
    public function supportsClass($class)
    {
        return $class === OpenIdAccount::class;
    }
}
