<?php
declare(strict_types=1);

namespace RZ\Roadiz\Webhook\Message;

use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\RequestInterface;
use RZ\Roadiz\Message\AsyncMessage;
use RZ\Roadiz\Message\HttpRequestMessage;

final class NetlifyBuildHookMessage implements AsyncMessage, HttpRequestMessage
{
    private string $uri;

    /**
     * @param string $uri
     */
    public function __construct(string $uri)
    {
        $this->uri = $uri;
    }

    public function getRequest(): RequestInterface
    {
        return new Request('POST', $this->uri);
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return [
            'debug' => false,
            'timeout' => 3
        ];
    }
}
