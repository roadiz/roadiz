<?php
declare(strict_types=1);

namespace RZ\Roadiz\Webhook\Message;

use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\RequestInterface;
use RZ\Roadiz\Message\AsyncMessage;
use RZ\Roadiz\Message\HttpRequestMessage;

final class GitlabPipelineTriggerMessage implements AsyncMessage, HttpRequestMessage
{
    private string $uri;
    private string $token;
    private string $ref;
    private ?array $variables;

    /**
     * @param string $uri
     * @param string $token
     * @param string $ref
     * @param array|null $variables
     */
    public function __construct(string $uri, string $token, string $ref = 'main', ?array $variables = null)
    {
        $this->uri = $uri;
        $this->token = $token;
        $this->ref = $ref;
        $this->variables = $variables;
    }

    public function getRequest(): RequestInterface
    {
        $postBody = [
            'token' => $this->token,
            'ref' => $this->ref,
        ];
        if (null !== $this->variables) {
            $postBody['variables'] = $this->variables;
        }

        return new Request(
            'POST',
            $this->uri,
            [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept'     => 'application/json'
            ],
            http_build_query($postBody)
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
}
