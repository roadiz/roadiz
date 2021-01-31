<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Security;

class SaltGenerator extends RandomGenerator implements SaltGeneratorInterface
{
    public function generateSalt()
    {
        return strtr(base64_encode($this->getRandomNumber(24)), '{}', '-_');
    }
}
