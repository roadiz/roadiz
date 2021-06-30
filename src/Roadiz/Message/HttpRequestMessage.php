<?php
declare(strict_types=1);

namespace RZ\Roadiz\Message;

use Psr\Http\Message\RequestInterface;

interface HttpRequestMessage
{
    public function getRequest(): RequestInterface;
}
