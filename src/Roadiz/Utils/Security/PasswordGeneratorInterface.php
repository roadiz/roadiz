<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Security;

interface PasswordGeneratorInterface
{

    /**
     * @param  integer $length
     * @return string
     */
    public function generatePassword($length = 9);
}
