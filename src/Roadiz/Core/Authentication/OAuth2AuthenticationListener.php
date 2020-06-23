<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Authentication;

use GuzzleHttp\Client;
use Lcobucci\JWT\Parser;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Firewall\AbstractAuthenticationListener;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\Session\SessionAuthenticationStrategyInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class OAuth2AuthenticationListener extends AbstractAuthenticationListener
{
    const OAUTH_STATE_TOKEN = 'openid_state';

    /**
     * @var CsrfTokenManagerInterface
     */
    protected $csrfTokenManager;

    /**
     * @var Client
     */
    protected $client;

    /**
     * OAuth2AuthenticationListener constructor.
     *
     * @param TokenStorageInterface                  $tokenStorage
     * @param AuthenticationManagerInterface         $authenticationManager
     * @param SessionAuthenticationStrategyInterface $sessionStrategy
     * @param HttpUtils                              $httpUtils
     * @param string                                 $providerKey
     * @param AuthenticationSuccessHandlerInterface  $successHandler
     * @param AuthenticationFailureHandlerInterface  $failureHandler
     * @param CsrfTokenManagerInterface              $csrfTokenManager
     * @param array                                  $options
     * @param LoggerInterface|null                   $logger
     * @param EventDispatcherInterface|null          $dispatcher
     */
    public function __construct(
        TokenStorageInterface $tokenStorage,
        AuthenticationManagerInterface $authenticationManager,
        SessionAuthenticationStrategyInterface $sessionStrategy,
        HttpUtils $httpUtils,
        string $providerKey,
        AuthenticationSuccessHandlerInterface $successHandler,
        AuthenticationFailureHandlerInterface $failureHandler,
        CsrfTokenManagerInterface $csrfTokenManager,
        array $options = [],
        LoggerInterface $logger = null,
        EventDispatcherInterface $dispatcher = null
    ) {
        parent::__construct($tokenStorage, $authenticationManager, $sessionStrategy, $httpUtils, $providerKey,
            $successHandler, $failureHandler, $options, $logger, $dispatcher);
        $this->csrfTokenManager = $csrfTokenManager;
        $this->client = new Client([
            // You can set any number of default request options.
            'timeout'  => 2.0,
        ]);
        if (empty($options['openid_token_uri'])) {
            throw new \InvalidArgumentException('openid_token_uri option must not be empty');
        }
        if (empty($options['oauth_client_id'])) {
            throw new \InvalidArgumentException('oauth_client_id option must not be empty');
        }
        if (empty($options['oauth_client_secret'])) {
            throw new \InvalidArgumentException('oauth_client_secret option must not be empty');
        }
        if (empty($options['roles'])) {
            throw new \InvalidArgumentException('roles option must not be empty');
        }
    }


    /**
     * {@inheritdoc}
     */
    public function supports(Request $request): ?bool
    {
        return $this->requiresAuthentication($request) &&
            $request->query->has('state') &&
            $request->query->has('code') &&
            $request->query->has('scope') &&
            $request->query->has('authuser');
    }

    /**
     * @inheritDoc
     */
    protected function attemptAuthentication(Request $request)
    {
        /*
         * Verify CSRF token passed to OAuth2 Service provider
         */
        $state = $request->query->get('state');
        $stateToken = $this->csrfTokenManager->getToken(static::OAUTH_STATE_TOKEN);
        if ($stateToken->getValue() !== $state || !$this->csrfTokenManager->isTokenValid($stateToken)) {
            throw new AuthenticationException('State token is not valid');
        }
        $response = $this->client->post($this->options['openid_token_uri'], [
            'form_params' => [
                'code' => $request->query->get('code'),
                'client_id' => $this->options['oauth_client_id'] ?? '',
                'client_secret' => $this->options['oauth_client_secret'] ?? '',
                'redirect_uri' => $request->getSchemeAndHttpHost() . $request->getBaseUrl() . $this->options['check_path'],
                'grant_type' => 'authorization_code'
            ]
        ]);

        $jsonResponse = json_decode($response->getBody()->getContents(), true);
        if (empty($jsonResponse['id_token'])) {
            throw new AuthenticationException('JWT is missing from response.');
        }

        $jwt = (new Parser())->parse((string) $jsonResponse['id_token']);

        if (empty($jwt->getClaim('email'))) {
            throw new AuthenticationException('JWT does not contain email claim.');
        }

        return $this->authenticationManager->authenticate(new JwtAccountToken(
            (string) $jwt->getClaim('email'),
            (string) $jwt,
            $this->providerKey,
            $this->options['roles']
        ));
    }
}
