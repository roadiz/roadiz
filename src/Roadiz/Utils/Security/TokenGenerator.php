<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Security;

/**
 * @deprecated Use \RZ\Roadiz\Random\TokenGenerator
 * @package RZ\Roadiz\Utils\Security
 */
class TokenGenerator extends \RZ\Roadiz\Random\RandomGenerator implements \RZ\Roadiz\Random\TokenGeneratorInterface
{
    public function generateToken()
    {
        return rtrim(strtr(base64_encode($this->getRandomNumber()), '+/', '-_'), '=');
    }
}
