<?php
declare(strict_types=1);

namespace RZ\Roadiz\OpenId\Authentication\Validator;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use RZ\Roadiz\OpenId\Authentication\JwtAccountToken;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

class UserInfoValidator extends DiscoveryAwareValidator
{
    /**
     * @inheritDoc
     */
    public function __invoke(JwtAccountToken $token): void
    {
        /*
         * Verify User information endpoint
         */
        if (!empty($this->discovery->get('userinfo_endpoint')) &&
            null !== $token->getAccessToken()) {
            try {
                $client = new Client();
                $client->get($this->discovery->get('userinfo_endpoint'), [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $token->getAccessToken(),
                    ],
                ]);
            } catch (ClientException $e) {
                throw new BadCredentialsException('Userinfo cannot be fetch from Identity provider');
            }
        }
    }
}
