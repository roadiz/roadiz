<?php
declare(strict_types=1);

namespace RZ\Roadiz\OpenId\Authentication\Validator;

use Lcobucci\JWT\Parser;
use RZ\Roadiz\OpenId\Authentication\JwtAccountToken;

/**
 * Class DebugValidator
 *
 * @package RZ\Roadiz\OpenId\Authentication\Validator
 * @internal Use this validator just to stop authentication process and debug your JWT
 */
class DebugValidator implements JwtValidator
{
    /**
     * @inheritDoc
     */
    public function __invoke(JwtAccountToken $token): void
    {
        $jwt = (new Parser())->parse((string) $token->getCredentials()); // Parses from a string
        dump($jwt);
        die;
    }
}
