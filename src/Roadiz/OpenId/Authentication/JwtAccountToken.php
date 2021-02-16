<?php
declare(strict_types=1);

namespace RZ\Roadiz\OpenId\Authentication;

use Lcobucci\JWT\Token;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;
use Symfony\Component\Security\Core\User\UserInterface;

class JwtAccountToken extends AbstractToken
{
    protected Token $jwt;
    protected string $providerKey;
    protected ?string $accessToken;

    /**
     * @param string|UserInterface $user
     * @param Token                $jwt
     * @param string|null          $accessToken
     * @param string               $providerKey
     * @param array                $roles
     */
    public function __construct($user, Token $jwt, ?string $accessToken, string $providerKey, array $roles = [])
    {
        parent::__construct($roles);
        $this->setUser($user);
        $this->setAuthenticated(\count($roles) > 0);
        $this->jwt = $jwt;
        $this->providerKey = $providerKey;

        if ($jwt instanceof Token\Plain) {
            $this->setAttributes($jwt->claims()->all());
        }
        $this->accessToken = $accessToken;
    }

    /**
     * Returns the provider key.
     *
     * @return string The provider key
     */
    public function getProviderKey()
    {
        return $this->providerKey;
    }

    /**
     * {@inheritdoc}
     */
    public function __serialize(): array
    {
        return [$this->jwt, $this->accessToken, $this->providerKey, parent::__serialize()];
    }

    /**
     * {@inheritdoc}
     */
    public function __unserialize(array $data): void
    {
        [$this->jwt, $this->accessToken, $this->providerKey, $parentData] = $data;
        $parentData = \is_array($parentData) ? $parentData : unserialize($parentData);
        parent::__unserialize($parentData);
    }

    /**
     * @return string|null
     */
    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    /**
     * @inheritDoc
     */
    public function getCredentials()
    {
        return $this->jwt;
    }
}
