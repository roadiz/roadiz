<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Security;

use RZ\Roadiz\Random\RandomGenerator;
use RZ\Roadiz\Random\SaltGeneratorInterface;

/**
 * @deprecated Use RZ\Roadiz\Random\SaltGenerator
 * @package RZ\Roadiz\Utils\Security
 */
class SaltGenerator extends RandomGenerator implements SaltGeneratorInterface
{
    public function generateSalt()
    {
        return strtr(base64_encode($this->getRandomNumber(24)), '{}', '-_');
    }
}
