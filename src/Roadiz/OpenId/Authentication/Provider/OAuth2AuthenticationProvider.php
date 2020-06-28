<?php
declare(strict_types=1);

namespace RZ\Roadiz\OpenId\Authentication\Provider;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\ValidationData;
use RZ\Roadiz\Core\Bags\Settings;
use RZ\Roadiz\OpenId\Authentication\JwtAccountToken;
use RZ\Roadiz\OpenId\Discovery;
use Lcobucci\JWT\Signer\Key;
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
        Settings $settingsBag,
        string $providerKey,
        array $defaultRoles = ['ROLE_USER'],
        bool $hideUserNotFoundExceptions = true
    ) {
        $this->providerKey = $providerKey;
        $this->hideUserNotFoundExceptions = $hideUserNotFoundExceptions;
        $this->defaultRoles = $defaultRoles;
        $this->roleStrategy = $roleStrategy;
        $this->discovery = $discovery;
        $this->settingsBag = $settingsBag;
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

        /*
         * Verify JWT expiration datetime
         */
        $jwt = (new Parser())->parse((string) $token->getCredentials()); // Parses from a string
            $data = new ValidationData();
        if (!$jwt->validate($data)) {
            throw new BadCredentialsException('Bad JWT.');
        }

        /*
         * Verify JWT iss (issuer)
         */
        if (!empty($this->discovery->get('issuer')) &&
            in_array('iss', $this->discovery->get('claims_supported', []))) {
            if ((string) $jwt->getClaim('iss') !== $this->discovery->get('issuer')) {
                throw new BadCredentialsException('Bad JWT issuer.');
            }
        }

        /*
         * Verify JWT signature if asymmetric crypto is used
         */
        /*if (null !== $this->discovery->getJwks()) {
            if (in_array(
                (string) $jwt->getHeader('alg'),
                $this->discovery->get('id_token_signing_alg_values_supported', [])
            )) {
                if ((string) $jwt->getHeader('alg') === 'RS256') {
                    $signer = new \Lcobucci\JWT\Signer\Rsa\Sha256();
                    $publicKey = new Key('');
                } elseif ((string) $jwt->getHeader('alg') === 'HS256') {
                    $signer = new \Lcobucci\JWT\Signer\Hmac\Sha256();
                    $publicKey = new Key('');
                }

                if (!$jwt->verify($signer, $publicKey)) {
                    throw new BadCredentialsException('Bad JWT signature.');
                }
            }
        }*/

        /*
         * Verify User information endpoint
         */
        if (!empty($this->discovery->get('userinfo_endpoint')) &&
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
        /*
         * Check that Hosted Domain is the same as required by Roadiz
         */
        if ($jwt->hasClaim('hd')) {
            if ($jwt->getClaim('hd') !== trim((string) $this->settingsBag->get('openid_hd'))) {
                throw new BadCredentialsException(
                    'User ('.$jwt->getClaim('hd').') does not belong to Hosted Domain.'
                );
            }
        }

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
