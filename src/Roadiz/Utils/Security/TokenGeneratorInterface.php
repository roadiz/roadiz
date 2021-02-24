<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Security;

/**
 * @deprecated Use \RZ\Roadiz\Random\TokenGeneratorInterface
 * @package RZ\Roadiz\Utils\Security
 */
interface TokenGeneratorInterface
{
    /**
     * @return string
     */
    public function generateToken();
}
