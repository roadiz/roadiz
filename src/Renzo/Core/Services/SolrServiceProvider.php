<?php

namespace RZ\Renzo\Core\Services;

use Pimple\Container;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\SearchEngine\FullTextSearchHandler;
/**
 * Register Solr services for dependency injection container.
 */
class SolrServiceProvider implements \Pimple\ServiceProviderInterface
{
    /**
     * @param Pimple\Container $container [description]
     */
    public function register(Container $container)
    {
        if (isset($container['config']['solr']['endpoint'])) {

            $container['solr'] = function ($c) {
                $solrService = new \Solarium\Client($c['config']['solr']);
                $solrService->setDefaultEndpoint('localhost');
                return $solrService;
            };
            $container['solr.search.nodeSource'] = function ($c) {
                $searchNodesource = new FullTextSearchHandler($c['solr']);
                return $searchNodesource;
            };
        }
    }
}
