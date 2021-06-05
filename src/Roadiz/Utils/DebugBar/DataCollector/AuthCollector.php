<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\DebugBar\DataCollector;

use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use RZ\Roadiz\Core\Entities\User;
use RZ\Roadiz\OpenId\User\OpenIdAccount;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class AuthCollector extends DataCollector implements Renderable
{
    private TokenStorage $tokenStorage;

    /**
     * @param TokenStorage $tokenStorage
     */
    public function __construct(TokenStorage $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    private function getTokenInfo(?TokenInterface $token): array
    {
        if (null === $token) {
            return [
                'name' => 'Guest',
                'user' => [],
            ];
        }

        $user = $token->getUser();

        if (null !== $user && $user instanceof OpenIdAccount) {
            return [
                'name' => $user->getEmail(),
                'user' => [
                    'Token' => get_class($token),
                    'Token roles' => $token->getRoleNames(),
                    'Issuer' => $user->getIssuer(),
                    'User roles' => $user->getRoles(),
                    'Email' => $user->getEmail(),
                    'Name' => $user->getName(),
                    'Given name' => $user->getGivenName(),
                    'Family name' => $user->getFamilyName(),
                    'Phone number' => $user->getPhoneNumber(),
                    'Profile' => $user->getProfile(),
                    'Middle name' => $user->getMiddleName(),
                ]
            ];
        } elseif (null !== $user && $user instanceof User) {
            return [
                'name' => $user->getUsername(),
                'user' => [
                    'Token' => get_class($token),
                    'Token roles' => $token->getRoleNames(),
                    'User roles' => $user->getRoles(),
                    'Email' => $user->getEmail(),
                    'Last login' => $user->getLastLogin() ? $user->getLastLogin()->format("Y-m-d H:i:s") : null,
                ]
            ];
        } elseif (null !== $user && $user instanceof UserInterface) {
            return [
                'name' => $user->getUsername(),
                'user' => [
                    'Token' => get_class($token),
                    'Token roles' => $token->getRoleNames(),
                    'User roles' => $user->getRoles()
                ]
            ];
        } else {
            return [
                'name' => 'Guest',
                'user' => [
                    'Token' => get_class($token),
                    'Roles' =>  $token->getRoleNames(),
                ]
            ];
        }
    }

    /**
     * @inheritDoc
     */
    public function collect()
    {
        $token = $this->tokenStorage->getToken();
        $info = $this->getTokenInfo($this->tokenStorage->getToken());

        if (null !== $token && $token instanceof SwitchUserToken) {
            $user = $token->getOriginalToken()->getUser();
            $info['name'] .= sprintf(' (%s)', $user->getUsername());
            $info['user']['Token'] .= sprintf(' (%s)', get_class($token->getOriginalToken()));

            if ($user instanceof User || $user instanceof OpenIdAccount) {
                $info['user']['Email'] .= sprintf(' (%s)', $user->getEmail());
            }
        }

        return $info;
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return 'auth';
    }

    /**
     * @inheritDoc
     */
    public function getWidgets()
    {
        $widgets = [
            'auth' => [
                'icon' => 'user',
                'widget' => 'PhpDebugBar.Widgets.VariableListWidget',
                'map' => 'auth.user',
                'default' => '{}'
            ]
        ];
        $widgets['auth.name'] = [
            'icon' => 'user',
            'map' => 'auth.name',
            'default' => '',
        ];

        return $widgets;
    }
}
