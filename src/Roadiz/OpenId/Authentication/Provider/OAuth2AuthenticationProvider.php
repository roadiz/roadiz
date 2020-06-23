<?php
declare(strict_types=1);

namespace RZ\Roadiz\OpenId\Authentication\Provider;

use Lcobucci\JWT\Parser;
use Lcobucci\JWT\ValidationData;
use RZ\Roadiz\OpenId\User\OpenIdAccount;
use RZ\Roadiz\OpenId\Authentication\JwtAccountToken;
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
    protected $defaultRoles;
    /**
     * @var JwtRoleStrategy
     */
    protected $roleStrategy;

    /**
     * AccountAuthenticationProvider constructor.
     *
     * @param JwtRoleStrategy $roleStrategy
     * @param string          $providerKey
     * @param array           $defaultRoles
     * @param bool            $hideUserNotFoundExceptions
     */
    public function __construct(JwtRoleStrategy $roleStrategy, string $providerKey, array $defaultRoles = ['ROLE_USER'], bool $hideUserNotFoundExceptions = true)
    {
        $this->providerKey = $providerKey;
        $this->hideUserNotFoundExceptions = $hideUserNotFoundExceptions;
        $this->defaultRoles = $defaultRoles;
        $this->roleStrategy = $roleStrategy;
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

        // https://openid.net/specs/openid-connect-core-1_0.html#StandardClaims
        $user = new OpenIdAccount(
            (string) $jwt->getClaim('email'),
            $this->getRoles($token),
            $jwt
        );

        $authenticatedToken = new JwtAccountToken(
            $user,
            $token->getCredentials(),
            $this->providerKey,
            $this->getRoles($token)
        );
        $authenticatedToken->setAttributes($token->getAttributes());

        return $authenticatedToken;
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
        if ($token instanceof JwtAccountToken && $this->roleStrategy->supports($token)) {
            return $this->roleStrategy->getRoles($token);
        }
        return $this->defaultRoles;
    }
}
