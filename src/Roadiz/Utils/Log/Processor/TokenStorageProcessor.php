<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Log\Processor;

use RZ\Roadiz\Core\AbstractEntities\AbstractHuman;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class TokenStorageProcessor
{
    protected TokenStorageInterface $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    public function __invoke(array $record): array
    {
        if (null !== $this->tokenStorage->getToken() &&
            null !== $user = $this->tokenStorage->getToken()->getUser()) {
            if ($user instanceof AbstractHuman && $user instanceof UserInterface) {
                $record['context']['user'] = [
                    'username' => $user->getUsername(),
                    'email' => $user->getEmail(),
                    'roles' => $user->getRoles(),
                ];
            } elseif ($user instanceof UserInterface) {
                $record['context']['user'] = [
                    'username' => $user->getUsername(),
                    'roles' => $user->getRoles(),
                ];
            }
        }

        return $record;
    }
}
