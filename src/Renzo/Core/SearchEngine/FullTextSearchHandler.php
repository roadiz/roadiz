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

    public function __construct($client)
    {
        $this->client = $client;
    }

    private function solrSearch($q, $args = [])
    {
        $query = $this->client->createSelect();
        $query->setQuery($q);
        foreach ($args as $key => $value) {
            $query->addParam($key, $value);
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
        echo '<pre>';
        \Doctrine\Common\Util\Debug::dump($doc, 10, false);
        echo '</pre>';
        return $doc;
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
        $tmp = array();
        $tmp["fq"] = "document_type_s:NodesSources";
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
        $tmp = array();
        $tmp["fq"] = "document_type_s:NodesSources";
        $args = array_merge($tmp, $args);
        return $this->solrSearch($q, $args);
    }
}