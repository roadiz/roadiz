<?php
declare(strict_types=1);

namespace RZ\Roadiz\Message\Handler;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RZ\Roadiz\Message\GuzzleRequestMessage;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class GuzzleRequestMessageHandler implements MessageHandlerInterface
{
    private LoggerInterface $logger;
    private ?Client $client;

    /**
     * @param Client|null $client
     * @param LoggerInterface|null $logger
     */
    public function __construct(Client $client = null, ?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
        $this->client = $client ?? new Client();
    }

    public function __invoke(GuzzleRequestMessage $message)
    {
        try {
            $this->logger->info(sprintf(
                'HTTP request executed: %s %s',
                $message->getRequest()->getMethod(),
                $message->getRequest()->getUri()
            ));
            return $this->client->send($message->getRequest(), $message->getOptions());
        } catch (GuzzleException $exception) {
            $this->logger->error($exception->getMessage());
            return null;
        }
    }
}
