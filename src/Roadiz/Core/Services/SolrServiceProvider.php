<?php
/**
 * Copyright © 2014, Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
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
 * @file SolrServiceProvider.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Services;

use Pimple\Container;

use RZ\Roadiz\Core\SearchEngine\FullTextSearchHandler;

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
        $container['solr'] = function ($c) {
            if (isset($c['config']['solr']['endpoint'])) {
                $solrService = new \Solarium\Client($c['config']['solr']);
                $solrService->setDefaultEndpoint('localhost');
                return $solrService;
            } else {
                return null;
            }
        };

        $container['solr.ready'] = function ($c) {
            if (null !== $c['solr']) {
                // create a ping query
                $ping = $c['solr']->createPing();
                // execute the ping query
                try {
                    $c['solr']->ping($ping);
                    return true;
                } catch (\Exception $e) {
                    return false;
                }
            } else {
                return false;
            }
        };

        $container['solr.search.nodeSource'] = function ($c) {
            if (null !== $c['solr']) {
                $searchNodesource = new FullTextSearchHandler($c['solr']);
                return $searchNodesource;
            } else {
                return null;
            }
        };

        return $container;
    }
}
