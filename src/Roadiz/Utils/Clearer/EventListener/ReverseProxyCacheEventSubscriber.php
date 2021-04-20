<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Clearer\EventListener;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use Pimple\Container;
use RZ\Roadiz\Core\Events\Cache\CachePurgeRequestEvent;
use RZ\Roadiz\Core\Events\NodesSources\NodesSourcesUpdatedEvent;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ReverseProxyCacheEventSubscriber implements EventSubscriberInterface
{
    protected Container $container;

    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }
    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            CachePurgeRequestEvent::class => ['onBanRequest', 3],
            NodesSourcesUpdatedEvent::class => ['onPurgeRequest', 3],
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
     * @param CachePurgeRequestEvent $event
     */
    public function onBanRequest(CachePurgeRequestEvent $event)
    {
        if (!$this->supportConfig()) {
            return;
        }

        try {
            foreach ($this->createBanRequests() as $name => $request) {
                (new Client())->send($request, [
                    'debug' => $event->getKernel()->isDebug()
                ]);
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

        try {
            /** @var UrlGeneratorInterface $urlGenerator */
            $urlGenerator = $this->container['router'];
            $nodeSource = $event->getNodeSource();
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
                (new Client())->send($request, [
                    'debug' => false
                ]);
            }
        } catch (ClientException $e) {
            // do nothing
        }
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
}
