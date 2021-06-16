<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Clearer\EventListener;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use Pimple\Container;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Events\Cache\CachePurgeRequestEvent;
use RZ\Roadiz\Core\Events\NodesSources\NodesSourcesUpdatedEvent;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Workflow\Event\Event;

class ReverseProxyCacheEventSubscriber implements EventSubscriberInterface
{
    protected Container $container;
    private ?LoggerInterface $logger;

    /**
     * @param Container $container
     * @param LoggerInterface|null $logger
     */
    public function __construct(Container $container, ?LoggerInterface $logger = null)
    {
        $this->container = $container;
        $this->logger = $logger;
    }
    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            CachePurgeRequestEvent::class => ['onBanRequest', 3],
            NodesSourcesUpdatedEvent::class => ['onPurgeRequest', 3],
            'workflow.node.completed' => ['onNodeWorkflowCompleted', 3],
        ];
    }

    /**
     * @return bool
     */
    protected function supportConfig(): bool
    {
        return isset($this->container['config']['reverseProxyCache']) &&
            count($this->container['config']['reverseProxyCache']['frontend']) > 0;
    }

    /**
     * @param Event $event
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function onNodeWorkflowCompleted(Event $event): void
    {
        $node = $event->getSubject();
        if ($node instanceof Node) {
            if (!$this->supportConfig()) {
                return;
            }
            foreach ($node->getNodeSources() as $nodeSource) {
                $this->purgeNodesSources($nodeSource);
            }
        }
    }

    /**
     * @param CachePurgeRequestEvent $event
     */
    public function onBanRequest(CachePurgeRequestEvent $event)
    {
        if (!$this->supportConfig()) {
            return;
        }

        try {
            foreach ($this->createBanRequests() as $name => $request) {
                $this->sendRequest($request);
                $event->addMessage(
                    'Reverse proxy cache cleared.',
                    static::class,
                    'Reverse proxy cache ['.$name.']'
                );
            }
        } catch (ClientException $e) {
            $event->addError(
                $e->getMessage(),
                static::class,
                'Reverse proxy cache'
            );
        } catch (ConnectException $e) {
            $event->addError(
                $e->getMessage(),
                static::class,
                'Reverse proxy cache'
            );
        }
    }

    /**
     * @param NodesSourcesUpdatedEvent $event
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function onPurgeRequest(NodesSourcesUpdatedEvent $event)
    {
        if (!$this->supportConfig()) {
            return;
        }

        $this->purgeNodesSources($event->getNodeSource());
    }

    /**
     * @return \GuzzleHttp\Psr7\Request[]
     */
    protected function createBanRequests()
    {
        $requests = [];
        foreach ($this->container['config']['reverseProxyCache']['frontend'] as $name => $frontend) {
            $requests[$name] = new \GuzzleHttp\Psr7\Request(
                'BAN',
                'http://' . $frontend['host'],
                [
                    'Host' => $frontend['domainName']
                ]
            );
        }
        return $requests;
    }

    /**
     * @param NodesSources $nodeSource
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function purgeNodesSources(NodesSources $nodeSource): void
    {
        try {
            /** @var UrlGeneratorInterface $urlGenerator */
            $urlGenerator = $this->container['router'];
            while (!$nodeSource->isReachable()) {
                $nodeSource = $nodeSource->getParent();
                if (null === $nodeSource) {
                    return;
                }
            }

            $purgeRequests = $this->createPurgeRequests($urlGenerator->generate(
                RouteObjectInterface::OBJECT_BASED_ROUTE_NAME,
                [
                    RouteObjectInterface::ROUTE_OBJECT => $nodeSource,
                ]
            ));
            foreach ($purgeRequests as $request) {
                $this->sendRequest($request);
            }
        } catch (ClientException $e) {
            // do nothing
        }
    }

    /**
     * @param string $path
     *
     * @return \GuzzleHttp\Psr7\Request[]
     */
    protected function createPurgeRequests($path = "/")
    {
        $requests = [];
        foreach ($this->container['config']['reverseProxyCache']['frontend'] as $name => $frontend) {
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
     * @return ResponseInterface|null
     */
    protected function sendRequest(\GuzzleHttp\Psr7\Request $request): ?ResponseInterface
    {
        try {
            if (null !== $this->logger) {
                $this->logger->info(sprintf(
                    'Reverse proxy %s request: %s',
                    $request->getMethod(),
                    $request->getUri()
                ));
            }
            return (new Client())->send($request, [
                'debug' => false,
                'timeout' => 3
            ]);
        } catch (GuzzleException $exception) {
            $this->logger->error($exception->getMessage());
            return null;
        }
    }
}
