<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Exceptions;

/**
 * Exception raised when trying to create or update
 * an entity when a sibling already exists.
 */
class EntityAlreadyExistsException extends \RuntimeException
{
}
