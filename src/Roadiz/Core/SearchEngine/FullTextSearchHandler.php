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
 * @file FullTextSearchHandler.php
 * @author Maxime Constantinian
 */
namespace RZ\Roadiz\Core\SearchEngine;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\Tag;
use Solarium\Client;
use Solarium\Core\Query\Helper;

class FullTextSearchHandler
{
    protected $client = null;
    protected $em = null;
    protected $logger = null;

    /**
     * @param Client $client
     * @param EntityManager $em
     * @param LoggerInterface $logger
     */
    public function __construct(
        Client $client,
        EntityManager $em,
        LoggerInterface $logger = null
    ) {
        $this->client = $client;
        $this->em = $em;
        $this->logger = $logger;
    }

    /**
     * @param string  $q
     * @param array   $args
     * @param integer $rows
     * @param boolean $searchTags
     * @param integer $proximity Proximity matching: Lucene supports finding words are a within a specific distance away.
     *
     * @return array
     */
    private function solrSearch($q, $args = [], $rows = 20, $searchTags = false, $proximity = 10000000)
    {
        if (!empty($q)) {
            $query = $this->client->createSelect();

            $q = trim($q);
            $qHelper = new Helper();
            $q = $qHelper->escapeTerm($q);

            $singleWord = strpos($q, ' ') === false ? true : false;

            /*
             * @see http://www.solrtutorial.com/solr-query-syntax.html
             */
            if ($singleWord) {
                $queryTxt = sprintf('(title:*%s*)^1.5 (collection_txt:*%s*)', $q, $q);
            } else {
                $queryTxt = sprintf('(title:"%s"~%d)^1.5 (collection_txt:"%s"~%d)', $q, $proximity, $q, $proximity);
            }

            /*
             * Search in node-sources tags name…
             */
            if ($searchTags) {
                if ($singleWord) {
                    $queryTxt .= sprintf(' (tags_txt:*%s*)', $q);
                } else {
                    $queryTxt .= sprintf(' (tags_txt:"%s"~%d)', $q, $proximity);
                }
            }
            $filterQueries = [];
            $query->setQuery($queryTxt);
            foreach ($args as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $k => $v) {
                        $filterQueries["fq" . $k] = $v;
                        $query->addFilterQuery([
                            "key" => "fq" . $k,
                            "query" => $v,
                        ]);
                    }
                } else {
                    $query->addParam($key, $value);
                }
            }
            $query->addSort('score', $query::SORT_DESC);
            $query->setRows($rows);

            if (null !== $this->logger) {

                $this->logger->debug('[Solr] Request node-sources search…', [
                    'query' => $queryTxt,
                    'filters' => $filterQueries,
                    'params' => $query->getParams(),
                ]);
            }

            $resultset = $this->client->select($query);
            $reponse = json_decode($resultset->getResponse()->getBody(), true);

            $doc = array_map(
                function ($n) use ($reponse) {
                    if (isset($reponse["highlighting"])) {
                        return [
                            "nodeSource" => $this->em->find(
                                'RZ\Roadiz\Core\Entities\NodesSources',
                                (int) $n["node_source_id_i"]
                            ),
                            "highlighting" => $reponse["highlighting"][$n['id']],
                        ];
                    }
                    return $this->em->find(
                        'RZ\Roadiz\Core\Entities\NodesSources',
                        $n["node_source_id_i"]
                    );
                },
                $reponse['response']['docs']
            );

            return $doc;
        } else {
            return null;
        }
    }

    private function argFqProcess(&$args)
    {
        if (!isset($args["fq"])) {
            $args["fq"] = [];
        }
        if (isset($args['visible'])) {
            $tmp = "node_visible_b:" . (($args['visible']) ? 'true' : 'false');
            unset($args['visible']);
            $args["fq"][] = $tmp;
        } else {
            $args["fq"][] = "node_visible_b:true";
        }

        // filter by tag or tags
        if (!empty($args['tags'])) {
            if ($args['tags'] instanceof Tag) {
                $args["fq"][] = "tags_txt:" . $args['tags']->getTranslatedTags()->first()->getName();
            } elseif (is_array($args['tags'])) {
                foreach ($args['tags'] as $tag) {
                    if ($tag instanceof Tag) {
                        $args["fq"][] = "tags_txt:" . $tag->getTranslatedTags()->first()->getName();
                    }
                }
            }
            unset($args['tags']);
        }

        if (!empty($args['nodeType'])) {
            if (is_array($args['nodeType']) || $args['nodeType'] instanceof Collection) {
                $orQuery = [];
                foreach ($args['nodeType'] as $nodeType) {
                    if ($nodeType instanceof NodeType) {
                        $orQuery[] = $nodeType->getName();
                    } else {
                        $orQuery[] = $nodeType;
                    }
                }
                $args["fq"][] = "node_type_s:(" . implode(' OR ', $orQuery) . ')';
            } elseif ($args['nodeType'] instanceof NodeType) {
                $args["fq"][] = "node_type_s:" . $args['nodeType']->getName();
            } else {
                $args["fq"][] = "node_type_s:" . $args['nodeType'];
            }
            unset($args['nodeType']);
        }

        if (isset($args['status'])) {
            $tmp = "node_status_i:";
            if (!is_array($args['status'])) {
                $tmp .= (string) $args['status'];
            } elseif ($args['status'][0] == "<=") {
                $tmp .= "[* TO " . (string) $args['status'][1] . "]";
            } elseif ($args['status'][0] == ">=") {
                $tmp .= "[" . (string) $args['status'][1] . " TO *]";
            }
            unset($args['status']);
            $args["fq"][] = $tmp;
        } else {
            $args["fq"][] = "node_status_i:" . (string) (Node::PUBLISHED);
        }
        return $args;
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
     * @param boolean $searchTags Search in tags too, even if a node don’t match
     * @param integer $proximity Proximity matching: Lucene supports finding words are a within a specific distance away.
     *
     * @return array
     */
    public function searchWithHighlight($q, $args = [], $rows = 20, $searchTags = false, $proximity = 10000000)
    {
        $args = $this->argFqProcess($args);
        $args["fq"][] = "document_type_s:NodesSources";
        $tmp = [];
        $tmp["hl"] = true;
        $tmp["hl.fl"] = "*";
        $tmp["hl.simple.pre"] = '<span class="solr-highlight">';
        $tmp["hl.simple.post"] = "</span>";
        $args = array_merge($tmp, $args);

        return $this->solrSearch($q, $args, $rows, $searchTags, $proximity);
    }

    /**
     * Search on Solr.
     *
     * * $q is the search criteria.
     * * $args is a array with solr query argument.
     * The common argument can be found [here](https://cwiki.apache.org/confluence/display/solr/Common+Query+Parameters)
     *  and for highlighting argument is [here](https://cwiki.apache.org/confluence/display/solr/Standard+Highlighter).
     *
     * You can use shortcuts in $args array to filter:
     *
     * * status (int)
     * * visible (boolean)
     * * nodeType (RZ\Roadiz\Core\Entities\NodeType or string)
     * * tags (RZ\Roadiz\Core\Entities\Tag or array of Tag)
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
     * @param int  $rows
     * @param boolean $searchTags Search in tags too, even if a node don’t match
     * @param integer $proximity Proximity matching: Lucene supports finding words are a within a specific distance away.
     *
     * @return array
     */
    public function search($q, $args = [], $rows = 20, $searchTags = false, $proximity = 10000000)
    {
        $args = $this->argFqProcess($args);
        $args["fq"][] = "document_type_s:NodesSources";
        $tmp = [];
        $args = array_merge($tmp, $args);
        return $this->solrSearch($q, $args, $rows, $searchTags, $proximity);
    }
}
