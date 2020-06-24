<?php
declare(strict_types=1);

namespace RZ\Roadiz\OpenId\Exception;

use Throwable;

class DiscoveryNotAvailableException extends \RuntimeException
{
    public function __construct($message = 'OpenID discovery is not configured', $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
