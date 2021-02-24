<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Security;

/**
 * @deprecated Use \RZ\Roadiz\Random\SaltGeneratorInterface
 * @package RZ\Roadiz\Utils\Security
 */
interface SaltGeneratorInterface
{
    /**
     * @return string
     */
    public function generateSalt();
}
