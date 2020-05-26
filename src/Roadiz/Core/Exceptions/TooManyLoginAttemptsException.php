<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Exceptions;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

class TooManyLoginAttemptsException extends AuthenticationException
{
    /**
     * {@inheritdoc}
     */
    public function getMessageKey()
    {
        return 'Too many login attemps, wait before trying again.';
    }
}
