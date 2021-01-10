<?php
declare(strict_types=1);

namespace RZ\Roadiz\OpenId\Authentication\Provider;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Validation\RequiredConstraintsViolated;
use RZ\Roadiz\OpenId\Authentication\JwtAccountToken;
use RZ\Roadiz\OpenId\User\OpenIdAccount;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class OAuth2AuthenticationProvider implements AuthenticationProviderInterface
{
    /**
     * @var string
     */
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
     * @var Configuration
     */
    protected $jwtConfiguration;

    /**
     * @param Configuration $jwtConfiguration
     * @param JwtRoleStrategy $roleStrategy
     * @param string $providerKey
     * @param array $defaultRoles
     * @param bool $hideUserNotFoundExceptions
     */
    public function __construct(
        Configuration $jwtConfiguration,
        JwtRoleStrategy $roleStrategy,
        string $providerKey,
        array $defaultRoles = ['ROLE_USER'],
        bool $hideUserNotFoundExceptions = true
    ) {
        $this->providerKey = $providerKey;
        $this->hideUserNotFoundExceptions = $hideUserNotFoundExceptions;
        $this->defaultRoles = $defaultRoles;
        $this->roleStrategy = $roleStrategy;
        $this->jwtConfiguration = $jwtConfiguration;
    }

    /**
     * @inheritDoc
     */
    public function authenticate(TokenInterface $token)
    {
        if (!$this->supports($token)) {
            throw new AuthenticationException('The token is not supported by this authentication provider.');
        }
        /** @var Token $jwt */
        $jwt = $token->getCredentials();
        $constraints = $this->jwtConfiguration->validationConstraints();

        if (!($jwt instanceof Plain)) {
            throw new AuthenticationException(
                'JWT token must be instance of ' . Plain::class
            );
        }

        try {
            $this->jwtConfiguration->validator()->assert($jwt, ...$constraints);
        } catch (RequiredConstraintsViolated $e) {
            throw new AuthenticationException($e->getMessage());
        }

        // https://openid.net/specs/openid-connect-core-1_0.html#StandardClaims
        $user = new OpenIdAccount(
            (string) $token->getUsername(),
            $this->getRoles($token),
            $jwt
        );

        $accessToken = null;
        if ($token instanceof JwtAccountToken) {
            $accessToken = $token->getAccessToken() ?? $token->getCredentials()->toString();
        }

        $authenticatedToken = new JwtAccountToken(
            $user,
            $token->getCredentials(),
            $accessToken,
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
        $roles = $this->defaultRoles;
        if ($token instanceof JwtAccountToken && $this->roleStrategy->supports($token)) {
            $roles = array_merge($roles, $this->roleStrategy->getRoles($token));
        }

        return array_unique($roles);
    }
}
