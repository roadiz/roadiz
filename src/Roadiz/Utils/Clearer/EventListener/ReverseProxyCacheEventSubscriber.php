<?php
/**
 * Copyright (c) 2018. Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file ReverseProxyCacheEventSubscriber.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace RZ\Roadiz\Utils\Clearer\EventListener;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Pimple\Container;
use RZ\Roadiz\Core\Events\CacheEvents;
use RZ\Roadiz\Core\Events\FilterCacheEvent;
use RZ\Roadiz\Core\Events\FilterNodesSourcesEvent;
use RZ\Roadiz\Core\Events\NodesSourcesEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ReverseProxyCacheEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * ReverseProxyCacheEventSubscriber constructor.
     *
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
            CacheEvents::PURGE_REQUEST => ['onBanRequest', 3],
            NodesSourcesEvents::NODE_SOURCE_UPDATED => ['onPurgeRequest', 3],
        ];
    }

    /**
     * @return bool
     */
    protected function supportConfig()
    {
        return isset($this->container['config']['reverseProxyCache']) &&
            count($this->container['config']['reverseProxyCache']['frontend']) > 0;
    }

    /**
     * @param FilterCacheEvent $event
     */
    public function onBanRequest(FilterCacheEvent $event)
    {
        if (!$this->supportConfig()) {
            return;
        }

        try {
            foreach ($this->createBanRequests() as $name => $request) {
                (new Client())->send($request, ['debug' => $event->getKernel()->isDebug()]);
                $event->addMessage(
                    'Reverse proxy cache cleared.',
                    static::class,
                    'reverseProxyCache ['.$name.']'
                );
            }
        } catch (ClientException $e) {
            $event->addError(
                $e->getMessage(),
                static::class,
                'reverseProxyCache'
            );
        }
    }

    /**
     * @param FilterNodesSourcesEvent $event
     */
    public function onPurgeRequest(FilterNodesSourcesEvent $event)
    {
        if (!$this->supportConfig()) {
            return;
        }

        try {
            /** @var UrlGeneratorInterface $urlGenerator */
            $urlGenerator = $this->container['router'];
            $nodeSource = $event->getNodeSource();
            while (!$nodeSource->getNode()->getNodeType()->isReachable()) {
                $nodeSource = $nodeSource->getParent();
                if (null === $nodeSource) {
                    return;
                }
            }
            foreach ($this->createPurgeRequests($urlGenerator->generate($nodeSource)) as $request) {
                (new Client())->send($request, ['debug' => false]);
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
                    'Host' => $frontend['domainName'],
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
                    'Host' => $frontend['domainName'],
                ]
            );
        }
        return $requests;
    }
}
