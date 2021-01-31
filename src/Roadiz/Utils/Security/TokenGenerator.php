<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Security;

class TokenGenerator extends RandomGenerator implements TokenGeneratorInterface
{
    public function generateToken()
    {
        return rtrim(strtr(base64_encode($this->getRandomNumber()), '+/', '-_'), '=');
    }
}
