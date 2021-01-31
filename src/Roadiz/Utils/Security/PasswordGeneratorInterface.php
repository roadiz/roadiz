<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Security;

interface PasswordGeneratorInterface
{
    /**
     * @param int $length
     * @return string
     */
    public function generatePassword(int $length = 12);
}
