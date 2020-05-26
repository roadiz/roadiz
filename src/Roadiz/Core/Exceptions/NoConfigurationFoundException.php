<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Exceptions;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

/**
 * Exception raised when no configuration file has been found.
 */
class NoConfigurationFoundException extends InvalidConfigurationException
{
    protected $message = "No configuration file was found. Make sure that conf/config.json exists.";
}
