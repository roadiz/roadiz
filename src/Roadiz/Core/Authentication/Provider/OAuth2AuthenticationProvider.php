<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Authentication\Provider;

use Lcobucci\JWT\Parser;
use Lcobucci\JWT\ValidationData;
use RZ\Roadiz\Core\Authentication\JwtAccount;
use RZ\Roadiz\Core\Authentication\JwtAccountToken;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

class OAuth2AuthenticationProvider implements AuthenticationProviderInterface
{
    protected $providerKey;
    /**
     * @var bool
     */
    protected $hideUserNotFoundExceptions;
    /**
     * @var array|string[]
     */
    protected $roles;

    /**
     * AccountAuthenticationProvider constructor.
     *
     * @param string $providerKey
     * @param array  $roles
     * @param bool   $hideUserNotFoundExceptions
     */
    public function __construct(string $providerKey, array $roles = ['ROLE_USER'], bool $hideUserNotFoundExceptions = true)
    {
        $this->providerKey = $providerKey;
        $this->hideUserNotFoundExceptions = $hideUserNotFoundExceptions;
        $this->roles = $roles;
    }

    /**
     * @inheritDoc
     */
    public function authenticate(TokenInterface $token)
    {
        if (!$this->supports($token)) {
            throw new AuthenticationException('The token is not supported by this authentication provider.');
        }

        /*
         * TODO: Here checks identity against OAuth2 identity provider
         */
        $jwt = (new Parser())->parse((string) $token->getCredentials()); // Parses from a string
        $data = new ValidationData();
        if (!$jwt->validate($data)) {
            throw new BadCredentialsException('Bad JWT.');
        }

//        $user = new JwtAccount(
//            $token->getUsername(),
//            $this->getRoles($token),
//            $jwt->hasClaim('name') ? $jwt->getClaim('name') : null,
//            $jwt->hasClaim('family_name') ? $jwt->getClaim('family_name', null) : null,
//            $jwt->hasClaim('given_name') ? $jwt->getClaim('given_name', null) : null,
//            $jwt->hasClaim('picture') ? $jwt->getClaim('picture', null) : null
//        );

        return new JwtAccountToken($token, $token->getCredentials(), $this->providerKey, $this->getRoles($token));
    }

    /**
     * @inheritDoc
     */
    public function supports(TokenInterface $token)
    {
        return $token instanceof JwtAccountToken && $this->providerKey === $token->getProviderKey();
    }

    protected function getRoles(TokenInterface $token)
    {
        return $this->roles;
    }
}
