<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Services;

use Doctrine\Persistence\ManagerRegistry;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\Roadiz\Core\SearchEngine\DocumentSearchHandler;
use RZ\Roadiz\Core\SearchEngine\Indexer\DocumentIndexer;
use RZ\Roadiz\Core\SearchEngine\Indexer\FolderIndexer;
use RZ\Roadiz\Core\SearchEngine\Indexer\IndexerFactory;
use RZ\Roadiz\Core\SearchEngine\Indexer\NodeIndexer;
use RZ\Roadiz\Core\SearchEngine\Indexer\NodesSourcesIndexer;
use RZ\Roadiz\Core\SearchEngine\Indexer\TagIndexer;
use RZ\Roadiz\Core\SearchEngine\Message\Handler\SolrDeleteMessageHandler;
use RZ\Roadiz\Core\SearchEngine\Message\Handler\SolrReindexMessageHandler;
use RZ\Roadiz\Core\SearchEngine\Message\SolrDeleteMessage;
use RZ\Roadiz\Core\SearchEngine\Message\SolrReindexMessage;
use RZ\Roadiz\Core\SearchEngine\NodeSourceSearchHandler;
use RZ\Roadiz\Core\SearchEngine\NodeSourceSearchHandlerInterface;
use RZ\Roadiz\Core\SearchEngine\SolariumFactory;
use RZ\Roadiz\Core\SearchEngine\SolariumFactoryInterface;
use RZ\Roadiz\Core\SearchEngine\Subscriber\SolariumSubscriber;
use RZ\Roadiz\Markdown\MarkdownInterface;
use Solarium\Client;
use Solarium\Core\Client\Adapter\AdapterInterface;
use Solarium\Core\Client\Adapter\Curl;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Messenger\Handler\HandlerDescriptor;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Register Solr services for dependency injection container.
 */
class SolrServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Container $container
     *
     * @return Container
     */
    public function register(Container $container)
    {
        $container[AdapterInterface::class] = function (Container $c) {
            $adapter = new Curl();
            if (isset($c['config']['solr']['endpoint'])) {
                $endpoints = $c['config']['solr']['endpoint'];
                $firstEndpoint = reset($endpoints);
                $adapter->setTimeout($firstEndpoint['timeout']);
            }
            return $adapter;
        };

        /**
         * @param Container $c
         *
         * @return null|Client
         */
        $container['solr'] = function (Container $c) {
            if (isset($c['config']['solr']['endpoint'])) {
                $options = $c['config']['solr'];
                if (isset($options['timeout'])) {
                    unset($options['timeout']);
                }
                $solrService = new Client(
                    $c[AdapterInterface::class],
                    $c['proxy.dispatcher'],
                    $options
                );
                $solrService->setDefaultEndpoint('localhost');
                return $solrService;
            } else {
                return null;
            }
        };

        /**
         * @param Container $c
         *
         * @return bool
         */
        $container['solr.ready'] = function (Container $c) {
            if (null !== $c['solr']) {
                $c['stopwatch']->start('Ping Solr');
                // create a ping query
                $ping = $c['solr']->createPing();
                // execute the ping query
                try {
                    $c['solr']->ping($ping);
                    $c['stopwatch']->stop('Ping Solr');
                    return true;
                } catch (\Exception $e) {
                    $c['stopwatch']->stop('Ping Solr');
                    return false;
                }
            } else {
                return false;
            }
        };

        /**
         * @deprecated
         */
        $container['solr.search.nodeSource'] = $container->factory(function (Container $c) {
            return $c[NodeSourceSearchHandlerInterface::class];
        });

        /**
         * @param Container $c
         * @return null|NodeSourceSearchHandler
         */
        $container[NodeSourceSearchHandlerInterface::class] = $container->factory(function (Container $c) {
            if ($c['solr.ready']) {
                return new NodeSourceSearchHandler($c['solr'], $c['em'], $c['logger']);
            } else {
                return null;
            }
        });

        /**
         * @param Container $c
         * @return null|DocumentSearchHandler
         */
        $container[DocumentSearchHandler::class] = $container->factory(function (Container $c) {
            if ($c['solr.ready']) {
                return new DocumentSearchHandler($c['solr'], $c['em'], $c['logger']);
            } else {
                return null;
            }
        });

        /**
         * @param Container $c
         * @return SolariumFactoryInterface
         */
        $container[SolariumFactoryInterface::class] = function (Container $c) {
            return new SolariumFactory(
                $c['solr'],
                $c['logger'],
                $c[MarkdownInterface::class],
                $c['proxy.dispatcher'],
                $c['factory.handler']
            );
        };

        $container[IndexerFactory::class] = function (Container $c) {
            return new IndexerFactory(new \Pimple\Psr11\Container($c));
        };

        $container[NodeIndexer::class] = $container->factory(function (Container $c) {
            return new NodeIndexer(
                $c['solr'],
                $c[ManagerRegistry::class],
                $c[SolariumFactoryInterface::class]
            );
        });

        $container[NodesSourcesIndexer::class] = $container->factory(function (Container $c) {
            return new NodesSourcesIndexer(
                $c['solr'],
                $c[ManagerRegistry::class],
                $c[SolariumFactoryInterface::class]
            );
        });

        $container[DocumentIndexer::class] = $container->factory(function (Container $c) {
            return new DocumentIndexer(
                $c['solr'],
                $c[ManagerRegistry::class],
                $c[SolariumFactoryInterface::class]
            );
        });

        $container[TagIndexer::class] = $container->factory(function (Container $c) {
            return new TagIndexer(
                $c['solr'],
                $c[ManagerRegistry::class],
                $c[SolariumFactoryInterface::class]
            );
        });

        $container[FolderIndexer::class] = $container->factory(function (Container $c) {
            return new FolderIndexer(
                $c['solr'],
                $c[ManagerRegistry::class],
                $c[SolariumFactoryInterface::class]
            );
        });

        /*
         * Add custom event subscribers to the general dispatcher.
         *
         * Important: do not check here if Solr respond, not to request
         * solr server at each HTTP request.
         */
        $container->extend('dispatcher', function (EventDispatcher $dispatcher, Container $c) {
            $dispatcher->addSubscriber(
                new SolariumSubscriber(
                    $c[MessageBusInterface::class]
                )
            );
            return $dispatcher;
        });


        /*
         * Handlers
         */
        $container->extend('messenger.handlers', function (array $handlers, Container $c) {
            return array_merge($handlers, [
                SolrDeleteMessage::class => [
                    $c[SolrDeleteMessageHandler::class]
                ],
                SolrReindexMessage::class => [
                    $c[SolrReindexMessageHandler::class]
                ],
            ]);
        });

        $container[SolrDeleteMessageHandler::class] = function (Container $c) {
            return new HandlerDescriptor(
                new SolrDeleteMessageHandler($c[IndexerFactory::class], $c['logger.messenger']),
                [
                    'handles' => SolrDeleteMessage::class,
                ]
            );
        };
        $container[SolrReindexMessageHandler::class] = function (Container $c) {
            return new HandlerDescriptor(
                new SolrReindexMessageHandler($c[IndexerFactory::class], $c['logger.messenger']),
                [
                    'handles' => SolrReindexMessage::class,
                ]
            );
        };

        return $container;
    }
}
