<?php
/**
 * Copyright (c) 2016. Ambroise Maupate and Julien Blanchet
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
 * @file AbstractSearchHandler.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */
namespace RZ\Roadiz\Core\SearchEngine;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Solarium\Core\Client\Client;
use Solarium\QueryType\Select\Query\Component\BoostQuery;
use Solarium\QueryType\Select\Query\Query;

abstract class AbstractSearchHandler
{
    /**
     * @var Client|null
     */
    protected $client = null;
    /**
     * @var EntityManagerInterface|null
     */
    protected $em = null;
    /**
     * @var LoggerInterface|null
     */
    protected $logger = null;

    /**
     * @param Client $client
     * @param EntityManagerInterface $em
     * @param LoggerInterface $logger
     */
    public function __construct(
        Client $client,
        EntityManagerInterface $em,
        LoggerInterface $logger = null
    ) {
        $this->client = $client;
        $this->em = $em;
        $this->logger = $logger;
    }

    /**
     * @return string
     */
    abstract protected function getDocumentType();

    /**
     * @param $response
     * @return array
     */
    abstract protected function parseSolrResponse($response);

    /**
     * @param $args
     * @return mixed
     */
    abstract protected function argFqProcess(&$args);

    /**
     * @param string  $q
     * @param array   $args
     * @param integer $rows
     * @param boolean $searchTags
     * @param integer $proximity Proximity matching: Lucene supports finding words are a within a specific distance away.
     * @param integer $page
     *
     * @return array
     */
    abstract protected function nativeSearch($q, $args = [], $rows = 20, $searchTags = false, $proximity = 10000000, $page = 1);

    /**
     * Create Solr Select query. Override it to add DisMax fields and rules.
     *
     * @param array $args
     * @param int $rows
     * @param int $page
     * @return Query
     */
    protected function createSolrQuery(array &$args = [], $rows = 20, $page = 1)
    {
        $query = $this->client->createSelect();

        foreach ($args as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $k => $v) {
                    $query->addFilterQuery([
                        "key" => "fq" . $k,
                        "query" => $v,
                    ]);
                }
            } else {
                $query->addParam($key, $value);
            }
        }
        /**
         * Add start if not first page.
         */
        if ($page > 1) {
            $query->setStart(($page - 1) * $rows);
        }
        $query->addSort('score', $query::SORT_DESC);
        $query->setRows($rows);

        return $query;
    }

    /**
     * @param array $response
     * @return int
     */
    protected function parseResultCount($response)
    {
        if (null !== $response && isset($response['response']['numFound'])) {
            return (int) $response['response']['numFound'];
        }

        return 0;
    }

    /**
     * Search on Solr with pre-filled argument for highlighting
     *
     * * $q is the search criteria.
     * * $args is a array with solr query argument.
     * The common argument can be found [here](https://cwiki.apache.org/confluence/display/solr/Common+Query+Parameters)
     *  and for highlighting argument is [here](https://cwiki.apache.org/confluence/display/solr/Standard+Highlighter).
     *
     * @param string $q
     * @param array $args
     * @param int $rows
     * @param boolean $searchTags Search in tags/folders too, even if a node don’t match
     * @param int $proximity Proximity matching: Lucene supports finding words are a within a specific distance away.
     * @param int $page
     *
     * @return array Return a array of **tuple** for each result.
     * [document, highlighting] for Documents and [nodeSource, highlighting]
     */
    public function searchWithHighlight($q, $args = [], $rows = 20, $searchTags = false, $proximity = 10000000, $page = 1)
    {
        $args = $this->argFqProcess($args);
        $args["fq"][] = "document_type_s:" . $this->getDocumentType();
        $tmp = [];
        $tmp["hl"] = true;
        $tmp["hl.fl"] = "collection_txt";
        $tmp["hl.simple.pre"] = '<span class="solr-highlight">';
        $tmp["hl.simple.post"] = '</span>';
        $args = array_merge($tmp, $args);

        $response = $this->nativeSearch($q, $args, $rows, $searchTags, $proximity, $page);
        return $this->parseSolrResponse($response);
    }

    /**
     * ## Search on Solr.
     *
     * * $q is the search criteria.
     * * $args is a array with solr query argument.
     * The common argument can be found [here](https://cwiki.apache.org/confluence/display/solr/Common+Query+Parameters)
     *  and for highlighting argument is [here](https://cwiki.apache.org/confluence/display/solr/Standard+Highlighter).
     *
     * You can use shortcuts in $args array to filter:
     *
     * ### For node-sources:
     *
     * * status (int)
     * * visible (boolean)
     * * nodeType (RZ\Roadiz\Core\Entities\NodeType or string or array)
     * * tags (RZ\Roadiz\Core\Entities\Tag or array of Tag)
     * * translation (RZ\Roadiz\Core\Entities\Translation)
     *
     * For other filters, use $args['fq'][] array, eg.
     *
     *     $args["fq"][] = "title:My title";
     *
     * this explicitly filter by title.
     *
     *
     * @param string $q
     * @param array  $args
     * @param int  $rows Results per page
     * @param boolean $searchTags Search in tags/folders too, even if a node don’t match
     * @param int $proximity Proximity matching: Lucene supports finding words are a within a specific distance away. Default 10000000
     * @param int $page Retrieve a specific page
     *
     * @return array Return an array of doctrine Entities (Document, NodesSources)
     */
    public function search($q, $args = [], $rows = 20, $searchTags = false, $proximity = 10000000, $page = 1)
    {
        $args = $this->argFqProcess($args);
        $args["fq"][] = "document_type_s:" . $this->getDocumentType();
        $tmp = [];
        $args = array_merge($tmp, $args);

        $response = $this->nativeSearch($q, $args, $rows, $searchTags, $proximity, $page);
        return $this->parseSolrResponse($response);
    }

    /**
     * @param $q
     * @param array $args
     * @param int $rows Useless var but keep it for retrocompatibility
     * @param bool $searchTags
     * @param int $proximity Proximity matching: Lucene supports finding words are a within a specific distance away. Default 10000000
     * @return int
     */
    public function count($q, $args = [], $rows = 0, $searchTags = false, $proximity = 10000000)
    {
        $args = $this->argFqProcess($args);
        $args["fq"][] = "document_type_s:" . $this->getDocumentType();
        $tmp = [];
        $args = array_merge($tmp, $args);

        $response = $this->nativeSearch($q, $args, $rows, $searchTags, $proximity);
        return $this->parseResultCount($response);
    }
}
