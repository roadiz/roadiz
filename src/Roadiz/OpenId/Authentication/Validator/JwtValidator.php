<?php
declare(strict_types=1);

namespace RZ\Roadiz\OpenId\Authentication\Validator;

use RZ\Roadiz\OpenId\Authentication\JwtAccountToken;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

interface JwtValidator
{
    /**
     * @param JwtAccountToken $token
     * @throws BadCredentialsException When jwt is not valid
     */
    public function __invoke(JwtAccountToken $token): void;
}
