<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Exceptions;

use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * Exception raised when no translation is available.
 */
class NoTranslationAvailableException extends ResourceNotFoundException
{
    protected $message = 'No translation is available with your requested locale. Try an another locale or verify that your site has at least one available translation.';
}
