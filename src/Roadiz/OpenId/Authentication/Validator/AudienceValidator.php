<?php
declare(strict_types=1);

namespace RZ\Roadiz\OpenId\Authentication\Validator;

use Lcobucci\JWT\Parser;
use RZ\Roadiz\Core\Bags\Settings;
use RZ\Roadiz\OpenId\Authentication\JwtAccountToken;
use RZ\Roadiz\OpenId\Authentication\Validator\JwtValidator;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

class AudienceValidator implements JwtValidator
{
    /**
     * @var Settings
     */
    protected $settingsBag;

    /**
     * HostedDomainValidator constructor.
     *
     * @param Settings $settingsBag
     */
    public function __construct(Settings $settingsBag)
    {
        $this->settingsBag = $settingsBag;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(JwtAccountToken $token): void
    {
        $jwt = (new Parser())->parse((string) $token->getCredentials()); // Parses from a string

        /*
         * Check that Hosted Domain is the same as required by Roadiz
         */
        if ($jwt->hasClaim('aud')) {
            if ($jwt->getClaim('aud') !== trim((string) $this->settingsBag->get('oauth_client_id'))) {
                throw new BadCredentialsException(
                    'Token does not belong to audience.'
                );
            }
        }
    }
}
