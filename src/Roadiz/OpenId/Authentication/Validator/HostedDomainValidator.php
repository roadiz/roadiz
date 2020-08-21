<?php
declare(strict_types=1);

namespace RZ\Roadiz\OpenId\Authentication\Validator;

use Lcobucci\JWT\Parser;
use RZ\Roadiz\Core\Bags\Settings;
use RZ\Roadiz\OpenId\Authentication\JwtAccountToken;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

class HostedDomainValidator implements JwtValidator
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
        $hostedDomain = trim((string) $this->settingsBag->get('openid_hd'));
        if ($jwt->hasClaim('hd') && !empty($hostedDomain)) {
            if ($jwt->getClaim('hd') !== $hostedDomain) {
                throw new BadCredentialsException(
                    'User ('.$jwt->getClaim('hd').') does not belong to Hosted Domain.'
                );
            }
        }
    }
}
