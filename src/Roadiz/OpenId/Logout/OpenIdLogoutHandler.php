<?php
declare(strict_types=1);

namespace RZ\Roadiz\OpenId\Logout;

use GuzzleHttp\Client;
use RZ\Roadiz\OpenId\Authentication\JwtAccountToken;
use RZ\Roadiz\OpenId\Discovery;
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
     * OpenIdLogoutHandler constructor.
     *
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
        }
    }
}
