<?php
declare(strict_types=1);

namespace RZ\Roadiz\Webhook\Message;

use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\RequestInterface;
use RZ\Roadiz\Message\AsyncMessage;
use RZ\Roadiz\Message\HttpRequestMessage;
use RZ\Roadiz\Webhook\Entity\WebhookInterface;

final class NetlifyBuildHookMessage implements AsyncMessage, HttpRequestMessage, WebhookMessage
{
    private string $uri;
    private ?array $payload;

    /**
     * @param string $uri
     * @param array|null $payload
     */
    public function __construct(string $uri, ?array $payload = null)
    {
        $this->uri = $uri;
        $this->payload = $payload;
    }

    public function getRequest(): RequestInterface
    {
        if (null !== $this->payload) {
            return new Request(
                'POST',
                $this->uri,
                [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept'     => 'application/json'
                ],
                http_build_query($this->payload)
            );
        }
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

    /**
     * @param WebhookInterface $webhook
     * @return static
     */
    public static function fromWebhook(WebhookInterface $webhook)
    {
        return new self($webhook->getUri(), $webhook->getPayload());
    }
}
