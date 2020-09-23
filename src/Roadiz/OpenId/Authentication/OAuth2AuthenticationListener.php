<?php
declare(strict_types=1);

namespace RZ\Roadiz\OpenId\Authentication;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Lcobucci\JWT\Parser;
use Psr\Log\LoggerInterface;
use RZ\Roadiz\OpenId\Discovery;
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
use function GuzzleHttp\Psr7\parse_query;

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
     * @var ?Discovery
     */
    protected $discovery;

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
     * @param Discovery                              $discovery
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
        Discovery $discovery,
        array $options = [],
        LoggerInterface $logger = null,
        EventDispatcherInterface $dispatcher = null
    ) {
        parent::__construct(
            $tokenStorage,
            $authenticationManager,
            $sessionStrategy,
            $httpUtils,
            $providerKey,
            $successHandler,
            $failureHandler,
            $options,
            $logger,
            $dispatcher
        );
        $this->csrfTokenManager = $csrfTokenManager;
        $this->client = new Client([
            // You can set any number of default request options.
            'timeout'  => 2.0,
        ]);
        if (empty($options['oauth_client_id'])) {
            throw new \InvalidArgumentException('oauth_client_id option must not be empty');
        }
        if (empty($options['oauth_client_secret'])) {
            throw new \InvalidArgumentException('oauth_client_secret option must not be empty');
        }
        if (empty($options['roles'])) {
            throw new \InvalidArgumentException('roles option must not be empty');
        }
        $this->discovery = $discovery;
    }


    /**
     * {@inheritdoc}
     */
    public function supports(Request $request): ?bool
    {
        return $this->requiresAuthentication($request) &&
            $request->query->has('state') &&
            $request->query->has('code') &&
            isset($this->options['check_path']) &&
            $this->httpUtils->checkRequestPath($request, $this->options['check_path']);
    }

    /**
     * @inheritDoc
     */
    protected function attemptAuthentication(Request $request)
    {
        /*
         * Verify CSRF token passed to OAuth2 Service provider,
         * State is an url_encoded string containing the "token" and other
         * optional data
         */
        if (null === $request->query->get('state')) {
            throw new AuthenticationException('State is not valid');
        }
        $state = parse_query($request->query->get('state'));
        $stateToken = $this->csrfTokenManager->getToken(static::OAUTH_STATE_TOKEN);

        if (!isset($state['token']) ||
            $stateToken->getValue() !== $state['token'] ||
            !$this->csrfTokenManager->isTokenValid($stateToken)) {
            throw new AuthenticationException('State token is not valid');
        }

        /*
         * Fetch _target_path parameter from OAuth2 state
         */
        if (isset($this->options['target_path_parameter']) &&
            isset($state[$this->options['target_path_parameter']])) {
            $request->query->set($this->options['target_path_parameter'], $state[$this->options['target_path_parameter']]);
        }

        try {
            $response = $this->client->post($this->discovery->get('token_endpoint'), [
                'form_params' => [
                    'code' => $request->query->get('code'),
                    'client_id' => $this->options['oauth_client_id'] ?? '',
                    'client_secret' => $this->options['oauth_client_secret'] ?? '',
                    'redirect_uri' => $request->getSchemeAndHttpHost() . $request->getBaseUrl() . $request->getPathInfo(),
                    'grant_type' => 'authorization_code'
                ]
            ]);
            $jsonResponse = json_decode($response->getBody()->getContents(), true);
        } catch (ClientException $e) {
            throw new AuthenticationException(
                'Cannot contact Identity provider to issue authorization_code.',
                $e->getCode(),
                $e
            );
        }

        if (empty($jsonResponse['id_token'])) {
            throw new AuthenticationException('JWT is missing from response.');
        }

        $jwt = (new Parser())->parse((string) $jsonResponse['id_token']);

        if (!$jwt->hasClaim($this->getUsernameClaimName()) || empty($jwt->getClaim($this->getUsernameClaimName()))) {
            throw new AuthenticationException('JWT does not contain “' . $this->getUsernameClaimName() . '” claim.');
        }

        return $this->authenticationManager->authenticate(new JwtAccountToken(
            (string) $jwt->getClaim($this->getUsernameClaimName()),
            (string) $jwt,
            !empty($jsonResponse['access_token']) ? $jsonResponse['access_token'] : null,
            $this->providerKey,
            $this->options['roles']
        ));
    }

    /**
     * @return string
     */
    protected function getUsernameClaimName(): string
    {
        if (!empty($this->options['username_claim'])) {
            return (string) $this->options['username_claim'];
        }
        return 'email';
    }
}