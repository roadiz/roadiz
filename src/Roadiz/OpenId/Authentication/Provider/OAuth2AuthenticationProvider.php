<?php
declare(strict_types=1);

namespace RZ\Roadiz\OpenId\Authentication\Provider;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Jose\Component\Core\JWK;
use Jose\Component\Core\Util\RSAKey;
use Jose\Component\Signature\Algorithm\RS256;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\ValidationData;
use RZ\Roadiz\Core\Bags\Settings;
use RZ\Roadiz\OpenId\Authentication\JwtAccountToken;
use RZ\Roadiz\OpenId\Authentication\Validator\JwtValidator;
use RZ\Roadiz\OpenId\Discovery;
use RZ\Roadiz\OpenId\Exception\DiscoveryNotAvailableException;
use RZ\Roadiz\OpenId\User\OpenIdAccount;
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
     * @var Discovery|null
     */
    protected $discovery;
    /**
     * @var Settings
     */
    protected $settingsBag;
    /**
     * @var JwtValidator[]
     */
    protected $validators;

    /**
     * AccountAuthenticationProvider constructor.
     *
     * @param Discovery|null  $discovery
     * @param JwtRoleStrategy $roleStrategy
     * @param Settings        $settingsBag
     * @param string          $providerKey
     * @param array           $defaultRoles
     * @param bool            $hideUserNotFoundExceptions
     */
    public function __construct(
        ?Discovery $discovery,
        JwtRoleStrategy $roleStrategy,
        string $providerKey,
        array $validators = [],
        array $defaultRoles = ['ROLE_USER'],
        bool $hideUserNotFoundExceptions = true
    ) {
        $this->providerKey = $providerKey;
        $this->hideUserNotFoundExceptions = $hideUserNotFoundExceptions;
        $this->defaultRoles = $defaultRoles;
        $this->roleStrategy = $roleStrategy;
        $this->discovery = $discovery;

        foreach ($validators as $validator) {
            if (!($validator instanceof JwtValidator)) {
                throw new \RuntimeException('Validators must implement ' . JwtValidator::class);
            }
        }
        $this->validators = $validators;
    }

    /**
     * @inheritDoc
     */
    public function authenticate(TokenInterface $token)
    {
        if (!$this->supports($token)) {
            throw new AuthenticationException('The token is not supported by this authentication provider.');
        }

        if (null === $this->discovery) {
            throw new DiscoveryNotAvailableException();
        }

        foreach ($this->validators as $validator) {
            $validator($token);
        }

        /** @var Token $jwt */
        $jwt = (new Parser())->parse((string) $token->getCredentials()); // Parses from a string

        // https://openid.net/specs/openid-connect-core-1_0.html#StandardClaims
        $user = new OpenIdAccount(
            (string) $jwt->getClaim('email'),
            $this->getRoles($token),
            $jwt
        );

        $accessToken = null;
        if ($token instanceof JwtAccountToken) {
            $accessToken = $token->getAccessToken();
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
