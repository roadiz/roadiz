<?php
/**
 * Copyright © 2014, REZO ZERO
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
 * Except as contained in this notice, the name of the REZO ZERO shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from the REZO ZERO SARL.
 *
 * @file FullTextSearchHandler.php
 * @copyright REZO ZERO 2014
 * @author Maxime Constantinian
 */
namespace RZ\Roadiz\Core\SearchEngine;

use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\Exceptions\SolrServerNotConfiguredException;
use RZ\Roadiz\Core\Exceptions\SolrServerNotAvailableException;
use Symfony\Component\HttpFoundation\Request;

use Solarium\QueryType\Update\Query\Query;
use Solarium\QueryType\Update\Query\Document\DocumentInterface;

class FullTextSearchHandler
{
    protected $client = null;

    public function __construct($client)
    {
        $this->client = $client;
    }

    private function solrSearch($q, $args = [])
    {
        if (!empty($q)) {
            $query = $this->client->createSelect();
            $query->setQuery('collection_txt:'.trim($q));

            foreach ($args as $key => $value) {
                if (is_array($value)){
                    foreach ($value as $k => $v) {
                        $query->addFilterQuery(array("key" => "fq".$k, "query"=>$v));
                    }
                } else {
                    $query->addParam($key, $value);
                }
            }
            $query->addSort('score', $query::SORT_DESC);

            //var_dump($query); exit();

            $resultset = $this->client->select($query);
            $reponse = json_decode($resultset->getResponse()->getBody(), true);

            $doc = array_map(
                function($n) use ($reponse) {
                    if (isset($reponse["highlighting"])) {
                        return array(
                                "nodeSource" => Kernel::getInstance()->getService('em')->find(
                                    'RZ\Roadiz\Core\Entities\NodesSources',
                                    (int) $n["node_source_id_i"]
                                ),
                                "highlighting" => $reponse["highlighting"][$n['id']]
                            );
                    }
                    return Kernel::getInstance()->getService('em')->find(
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
            $args["fq"] = array();
        }
        if (isset($args['visible'])) {
            $tmp = "node_visible_b:".(($args['visible']) ? 'true' : 'false');
            unset($args['visible']);
            $args["fq"][] = $tmp;
        } else {
            $args["fq"][] = "node_visible_b:true";
        }
        if (isset($args['status'])) {
            $tmp = "node_status_i:";
            if (!is_array($args['status'])) {
                $tmp .= (string)$args['status'];
            } elseif ($args['status'][0] == "<=") {
                $tmp .= "[* TO " . (string)$args['status'][1] . "]";
            } elseif ($args['status'][0] == ">=") {
                $tmp .= "[" . (string)$args['status'][1] . " TO *]";
            }
            unset($args['status']);
            $args["fq"][] = $tmp;
        } else {
            $args["fq"][] = "node_status_i:".(string)(Node::PUBLISHED);
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
     * @param array  $args
     *
     * @return array
     */
    public function searchWithHighlight($q, $args = array())
    {
        $args = $this->argFqProcess($args);
        $args["fq"][] = "document_type_s:NodesSources";
        $tmp = array();
        $tmp["hl"] = true;
        $tmp["hl.fl"] = "*";
        $tmp["hl.simple.pre"] = '<span class="solr-highlight">';
        $tmp["hl.simple.post"] =  "</span>";
        $args = array_merge($tmp, $args);

        return $this->solrSearch($q, $args);
    }

    /**
     * Search on Solr.
     *
     * * $q is the search criteria.
     * * $args is a array with solr query argument.
     * The common argument can be found [here](https://cwiki.apache.org/confluence/display/solr/Common+Query+Parameters)
     *  and for highlighting argument is [here](https://cwiki.apache.org/confluence/display/solr/Standard+Highlighter).
     *
     * @param string $q
     * @param array  $args
     *
     * @return array
     */
    public function search($q, $args = array())
    {
        $args = $this->argFqProcess($args);
        $args["fq"][] = "document_type_s:NodesSources";
        $tmp = array();
        $args = array_merge($tmp, $args);
        return $this->solrSearch($q, $args);
    }
}