<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Security;

interface SaltGeneratorInterface
{
    /**
     * @return string
     */
    public function generateSalt();
}
