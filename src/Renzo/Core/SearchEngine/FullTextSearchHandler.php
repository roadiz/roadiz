<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file FullTextSearchHandler.php
 * @copyright REZO ZERO 2014
 * @author Maxime Constantinian
 */

namespace RZ\Renzo\Core\SearchEngine;

use RZ\Renzo\Core\Entities\Node;
use RZ\Renzo\Core\Entities\NodesSources;
use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Exceptions\SolrServerNotConfiguredException;
use RZ\Renzo\Core\Exceptions\SolrServerNotAvailableException;
use Symfony\Component\HttpFoundation\Request;

use Solarium\QueryType\Update\Query\Query;
use Solarium\QueryType\Update\Query\Document\DocumentInterface;

class FullTextSearchHandler
{
    protected $client = null;

    public function __construct($client = null)
    {
        $this->client = $client;
        $this->client = Kernel::getService("solr");
    }

    private function solrSearch($q, $args = [])
    {
        $query = $this->client->createSelect();
        $query->setQuery($q);
        foreach ($args as $key => $value) {
            if (is_array($value)){
                foreach ($value as $k => $v) {
                    $query->addFilterQuery(array("key" => "fq".$k, "query"=>$v));
                }
            } else {
                $query->addParam($key, $value);
            }
        }

        $resultset = $this->client->select($query);

        $reponse = json_decode($resultset->getResponse()->getBody(), true);

        $doc = array_map(
                    function($n) use ($reponse) {
                        if (isset($reponse["highlighting"])) {
                            return array(
                                    "nodeSource" => Kernel::getInstance()->getService('em')->find('RZ\Renzo\Core\Entities\NodesSources', $n["node_source_id_i"]),
                                    "highlighting" => $reponse["highlighting"][$n['id']]
                                );
                        }
                        return Kernel::getInstance()->getService('em')->find('RZ\Renzo\Core\Entities\NodesSources', $n["node_source_id_i"]);
                    },
                    $reponse['response']['docs']);
        var_dump($this->client->createRequest($query)->getUri());
        echo '<pre>';
        \Doctrine\Common\Util\Debug::dump($doc, 10, false);
        echo '</pre>';
        return $doc;
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

    public function searchAction(Request $request, $q)
    {
        $args["visible"] = true;
        $args["status"] = array(">=", 30);
        $this->search($q, $args);
    }
}