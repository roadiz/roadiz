<?php
declare(strict_types=1);

namespace RZ\Roadiz\OpenId\Authentication\Validator;

use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\ValidationData;
use RZ\Roadiz\OpenId\Authentication\JwtAccountToken;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

class ExpirationValidator implements JwtValidator
{
    /**
     * @inheritDoc
     */
    public function __invoke(JwtAccountToken $token): void
    {
        /** @var Token $jwt */
        $jwt = (new Parser())->parse((string) $token->getCredentials()); // Parses from a string
        /*
         * Verify JWT expiration datetime
         */
        $data = new ValidationData();
        if (!$jwt->validate($data)) {
            throw new BadCredentialsException('Bad JWT.');
        }
    }
}
