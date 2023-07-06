<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Clearer\EventListener;

use Pimple\Container;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Events\Cache\CachePurgeRequestEvent;
use RZ\Roadiz\Core\Events\NodesSources\NodesSourcesUpdatedEvent;
use RZ\Roadiz\Message\GuzzleRequestMessage;
use RZ\Roadiz\Message\PurgeReverseProxyCacheMessage;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Workflow\Event\Event;

class ReverseProxyCacheEventSubscriber implements EventSubscriberInterface
{
    protected Container $container;
    private LoggerInterface $logger;
    private MessageBusInterface $bus;

    /**
     * @param Container $container
     * @param MessageBusInterface $bus
     * @param LoggerInterface|null $logger
     */
    public function __construct(Container $container, MessageBusInterface $bus, ?LoggerInterface $logger = null)
    {
        $this->container = $container;
        $this->logger = $logger ?? new NullLogger();
        $this->bus = $bus;
    }
    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
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

        foreach ($this->createBanRequests() as $name => $request) {
            $this->sendRequest($request);
            $event->addMessage(
                'Reverse proxy cache cleared.',
                static::class,
                'Reverse proxy cache ['.$name.']'
            );
        }
    }

    /**
     * @param NodesSourcesUpdatedEvent $event
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
     */
    protected function purgeNodesSources(NodesSources $nodeSource): void
    {
        try {
            $this->bus->dispatch(new Envelope(new PurgeReverseProxyCacheMessage($nodeSource->getId())));
        } catch (ExceptionInterface $exception) {
            $this->logger->error($exception->getMessage());
        }
    }

    /**
     * @param \GuzzleHttp\Psr7\Request $request
     * @return void
     */
    protected function sendRequest(\GuzzleHttp\Psr7\Request $request): void
    {
        try {
            $this->bus->dispatch(new Envelope(new GuzzleRequestMessage($request, [
                'debug' => false,
                'timeout' => 3
            ])));
        } catch (ExceptionInterface $exception) {
            $this->logger->error($exception->getMessage());
        }
    }
}
