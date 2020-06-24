<?php
declare(strict_types=1);

namespace RZ\Roadiz\OpenId\Authentication\Provider;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\ValidationData;
use RZ\Roadiz\OpenId\Authentication\JwtAccountToken;
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
     * AccountAuthenticationProvider constructor.
     *
     * @param Discovery|null  $discovery
     * @param JwtRoleStrategy $roleStrategy
     * @param string          $providerKey
     * @param array           $defaultRoles
     * @param bool            $hideUserNotFoundExceptions
     */
    public function __construct(
        ?Discovery $discovery,
        JwtRoleStrategy $roleStrategy,
        string $providerKey,
        array $defaultRoles = ['ROLE_USER'],
        bool $hideUserNotFoundExceptions = true
    ) {
        $this->providerKey = $providerKey;
        $this->hideUserNotFoundExceptions = $hideUserNotFoundExceptions;
        $this->defaultRoles = $defaultRoles;
        $this->roleStrategy = $roleStrategy;
        $this->discovery = $discovery;
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

        $jwt = (new Parser())->parse((string) $token->getCredentials()); // Parses from a string
        $data = new ValidationData();
        if (!$jwt->validate($data)) {
            throw new BadCredentialsException('Bad JWT.');
        }

        if (null !== $this->discovery &&
            !empty($this->discovery->get('userinfo_endpoint')) &&
            $token instanceof JwtAccountToken &&
            null !== $token->getAccessToken()) {
            try {
                $client = new Client();
                $client->get($this->discovery->get('userinfo_endpoint'), [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $token->getAccessToken()
                    ]
                ]);
            } catch (ClientException $e) {
                throw new BadCredentialsException('Userinfo cannot be fetch from Identity provider');
            }
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
            null,
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
