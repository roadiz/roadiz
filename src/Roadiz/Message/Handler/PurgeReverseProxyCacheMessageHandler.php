<?php
declare(strict_types=1);

namespace RZ\Roadiz\Message\Handler;

use Doctrine\Persistence\ManagerRegistry;
use Pimple\Psr11\Container;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Message\GuzzleRequestMessage;
use RZ\Roadiz\Message\PurgeReverseProxyCacheMessage;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\NoHandlerForMessageException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class PurgeReverseProxyCacheMessageHandler implements MessageHandlerInterface
{
    private UrlGeneratorInterface $urlGenerator;
    private array $configuration;
    private LoggerInterface $logger;
    private Container $busLocator;
    private ManagerRegistry $managerRegistry;

    /**
     * @param Container $busLocator
     * @param UrlGeneratorInterface $urlGenerator
     * @param array $configuration
     * @param ManagerRegistry $managerRegistry
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        Container $busLocator,
        UrlGeneratorInterface $urlGenerator,
        array $configuration,
        ManagerRegistry $managerRegistry,
        LoggerInterface $logger = null
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->configuration = $configuration;
        $this->logger = $logger ?? new NullLogger();
        $this->busLocator = $busLocator;
        $this->managerRegistry = $managerRegistry;
    }

    public function __invoke(PurgeReverseProxyCacheMessage $message)
    {
        $nodeSource = $this->managerRegistry
            ->getRepository(NodesSources::class)
            ->find($message->getNodeSourceId());
        if (null === $nodeSource) {
            $this->logger->error('NodesSources does not exist anymore.');
            return;
        }

        while (!$nodeSource->isReachable()) {
            $nodeSource = $nodeSource->getParent();
            if (null === $nodeSource) {
                return;
            }
        }

        $purgeRequests = $this->createPurgeRequests($this->urlGenerator->generate(
            RouteObjectInterface::OBJECT_BASED_ROUTE_NAME,
            [
                RouteObjectInterface::ROUTE_OBJECT => $nodeSource,
            ]
        ));
        foreach ($purgeRequests as $request) {
            $this->sendRequest($request);
        }
    }

    /**
     * @param string $path
     *
     * @return \GuzzleHttp\Psr7\Request[]
     */
    protected function createPurgeRequests(string $path = "/"): array
    {
        $requests = [];
        foreach ($this->configuration['reverseProxyCache']['frontend'] as $name => $frontend) {
            $requests[$name] = new \GuzzleHttp\Psr7\Request(
                Request::METHOD_PURGE,
                'http://' . $frontend['host'] . $path,
                [
                    'Host' => $frontend['domainName']
                ]
            );
        }
        return $requests;
    }

    /**
     * @param \GuzzleHttp\Psr7\Request $request
     * @return void
     */
    protected function sendRequest(\GuzzleHttp\Psr7\Request $request): void
    {
        try {
            $this->busLocator->get(MessageBusInterface::class)
                ->dispatch(new Envelope(new GuzzleRequestMessage($request, [
                'debug' => false,
                'timeout' => 3
            ])));
        } catch (NoHandlerForMessageException $exception) {
            $this->logger->error($exception->getMessage());
        }
    }
}
