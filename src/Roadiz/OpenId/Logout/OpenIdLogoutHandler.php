<?php
declare(strict_types=1);

namespace RZ\Roadiz\OpenId\Logout;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use RZ\Roadiz\OpenId\Authentication\JwtAccountToken;
use RZ\Roadiz\OpenId\Discovery;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Logout\LogoutHandlerInterface;

class OpenIdLogoutHandler implements LogoutHandlerInterface
{
    /**
     * @var Discovery
     */
    protected $discovery;

    /**
     * @param Discovery $discovery
     */
    public function __construct(Discovery $discovery)
    {
        $this->discovery = $discovery;
    }

    /**
     * @inheritDoc
     */
    public function logout(Request $request, Response $response, TokenInterface $token)
    {
        if ($this->discovery->has('revocation_endpoint') &&
            $token instanceof JwtAccountToken &&
            null !== $token->getAccessToken()) {
            try {
                $tokenToRevoke = $token->getAccessToken();
                $client = new Client();
                $client->post($this->discovery->get('revocation_endpoint'), [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $tokenToRevoke
                    ],
                    'form_params' => [
                        'token' => $tokenToRevoke
                    ]
                ]);
            } catch (ClientException $e) {
            }
        }
        /**
         * If a end_session_endpoint is available
         * just redirect user to it.
         */
        if ($this->discovery->has('end_session_endpoint') &&
            $token instanceof JwtAccountToken &&
            $response instanceof RedirectResponse) {
            $response->setTargetUrl($this->discovery->get('end_session_endpoint'));
            $response->setContent('Redirecting to ' . $this->discovery->get('end_session_endpoint'));
            $response->headers->set('Location', $this->discovery->get('end_session_endpoint'));
        }
    }
}
