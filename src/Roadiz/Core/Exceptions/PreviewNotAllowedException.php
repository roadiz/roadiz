<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Exceptions;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Exception raised when someone trying to see
 * preview entry point and has no rights to.
 */
class PreviewNotAllowedException extends AccessDeniedHttpException
{
    public function __construct($message = "You are not allowed to use preview entry point.")
    {
        parent::__construct($message);
    }
}
