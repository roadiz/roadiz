<?php
declare(strict_types=1);

namespace RZ\Roadiz\OpenId\Authentication\Validator;

use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Token;
use RZ\Roadiz\OpenId\Authentication\JwtAccountToken;
use RZ\Roadiz\OpenId\Discovery;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

class IssuerValidator extends DiscoveryAwareValidator
{
    /**
     * @inheritDoc
     */
    public function __invoke(JwtAccountToken $token): void
    {
        /** @var Token $jwt */
        $jwt = (new Parser())->parse((string) $token->getCredentials()); // Parses from a string
        
        /*
         * Verify JWT iss (issuer)
         */
        if (!empty($this->discovery->get('issuer')) &&
            in_array('iss', $this->discovery->get('claims_supported', []))) {
            if ((string) $jwt->getClaim('iss') !== $this->discovery->get('issuer')) {
                throw new BadCredentialsException('Bad JWT issuer.');
            }
        }
    }
}
