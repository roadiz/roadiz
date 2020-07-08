<?php
declare(strict_types=1);

namespace RZ\Roadiz\OpenId\Authentication\Validator;

use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Token;
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
        /** @var Token $jwt */
        $jwt = (new Parser())->parse((string) $token->getCredentials()); // Parses from a string
        
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
    }
}