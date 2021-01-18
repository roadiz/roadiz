<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Services;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\Roadiz\Core\Events\NodeNameSubscriber;
use RZ\Roadiz\Core\Routing\NodesSourcesPathAggregator;
use RZ\Roadiz\Core\Routing\OptimizedNodesSourcesGraphPathAggregator;
use RZ\Roadiz\Core\SearchEngine\Subscriber\DefaultNodesSourcesIndexingSubscriber;
use RZ\Roadiz\Utils\Node\NodeMover;
use RZ\Roadiz\Utils\Node\NodeTranstyper;
use Symfony\Component\EventDispatcher\EventDispatcher;

class NodeServiceProvider implements ServiceProviderInterface
{
    /**
     * Registers services on the given container.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     *
     * @param Container $container A container instance
     */
    public function register(Container $container)
    {
        $container[NodesSourcesPathAggregator::class] = function (Container $c) {
            /*
             * You can override this service to change NS path aggregator strategy
             */
            // return new NodesSourcesGraphPathAggregator();
            return new OptimizedNodesSourcesGraphPathAggregator($c['em']);
        };

        $container[NodeTranstyper::class] = function (Container $c) {
            return new NodeTranstyper($c['em'], $c['logger.doctrine']);
        };

        $container[NodeMover::class] = function (Container $c) {
            return new NodeMover(
                $c['em'],
                $c['router'],
                $c['factory.handler'],
                $c['proxy.dispatcher'],
                $c['nodesSourcesUrlCacheProvider'],
                $c['logger.doctrine']
            );
        };


        $container->extend('dispatcher', function (EventDispatcher $dispatcher, Container $c) {
            $dispatcher->addSubscriber(new NodeNameSubscriber(
                $c['logger.doctrine'],
                $c['utils.nodeNameChecker'],
                $c[NodeMover::class]
            ));
            $dispatcher->addSubscriber(
                new DefaultNodesSourcesIndexingSubscriber(
                    $c['factory.handler']
                )
            );
            return $dispatcher;
        });
    }
}
