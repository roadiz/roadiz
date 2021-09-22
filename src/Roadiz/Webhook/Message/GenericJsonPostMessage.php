<?php
declare(strict_types=1);

namespace RZ\Roadiz\Webhook\Message;

use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\RequestInterface;
use RZ\Roadiz\Message\AsyncMessage;
use RZ\Roadiz\Message\HttpRequestMessage;
use RZ\Roadiz\Webhook\Entity\Webhook;
use RZ\Roadiz\Webhook\Entity\WebhookInterface;

final class GenericJsonPostMessage implements AsyncMessage, HttpRequestMessage, WebhookMessage
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
        return new Request(
            'POST',
            $this->uri,
            [
                'Content-Type' => 'application/json',
                'Accept'     => 'application/json'
            ],
            json_encode($this->payload ?? [], JSON_NUMERIC_CHECK | JSON_THROW_ON_ERROR)
        );
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
     * @param Webhook $webhook
     * @return static
     */
    public static function fromWebhook(WebhookInterface $webhook)
    {
        return new self($webhook->getUri(), $webhook->getPayload());
    }
}
