<?php
declare(strict_types=1);

namespace RZ\Roadiz\OpenId\Authentication\Validator;

use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Token;
use RZ\Roadiz\Core\Bags\Settings;
use RZ\Roadiz\OpenId\Authentication\JwtAccountToken;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

/**
 * Class DebugValidator
 *
 * @package RZ\Roadiz\OpenId\Authentication\Validator
 * @internal
 */
class DebugValidator implements JwtValidator
{
    /**
     * @inheritDoc
     */
    public function __invoke(JwtAccountToken $token): void
    {
        /** @var Token $jwt */
        $jwt = (new Parser())->parse((string) $token->getCredentials()); // Parses from a string
        dump($jwt);
        die;
    }
}
