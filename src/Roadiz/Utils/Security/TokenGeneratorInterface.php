<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Security;

interface TokenGeneratorInterface
{
    /**
     * @return string
     */
    public function generateToken();
}
