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
 * @file NodeSourceSearchHandler.php
 * @author Maxime Constantinian
 */
namespace RZ\Roadiz\Core\SearchEngine;

use Doctrine\Common\Collections\Collection;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Entities\Translation;
use Solarium\Core\Query\Helper;

/**
 * Class NodeSourceSearchHandler
 * @package RZ\Roadiz\Core\SearchEngine
 */
class NodeSourceSearchHandler extends AbstractSearchHandler
{
    /**
     * Default Solr query builder.
     *
     * Extends this method to customize your Solr queries. Eg. to boost custom fields.
     *
     * @param string $q
     * @param array $args
     * @param bool $searchTags
     * @param int $proximity
     * @return string
     */
    protected function buildQuery($q, array &$args, $searchTags, $proximity)
    {
        $q = trim($q);
        $qHelper = new Helper();
        $q = $qHelper->escapeTerm($q);

        $singleWord = strpos($q, ' ') === false ? true : false;

        $titleField = 'title';

        /*
         * Use title_txt_LOCALE when search
         * is filtered by translation.
         */
        if (isset($args['translation']) && $args['translation'] instanceof Translation) {
            $titleField = 'title_txt_' . \Locale::getPrimaryLanguage($args['translation']->getLocale());
        }
        if (isset($args['locale']) && is_string($args['locale'])) {
            $titleField = 'title_txt_' . \Locale::getPrimaryLanguage($args['locale']);
        }

        /*
         * Search in node-sources tags name…
         */
        if ($searchTags) {
            /*
             * @see http://www.solrtutorial.com/solr-query-syntax.html
             */
            if ($singleWord) {
                return sprintf('(' . $titleField . ':%s*)^10 (collection_txt:%s*) (tags_txt:*%s*)', $q, $q, $q);
            } else {
                return sprintf('(' . $titleField . ':"%s"~%d)^10 (collection_txt:"%s"~%d) (tags_txt:"%s"~%d)', $q, $proximity, $q, $proximity, $q, $proximity);
            }
        } else {
            if ($singleWord) {
                return sprintf('(' . $titleField . ':%s*)^5 (collection_txt:%s*)', $q, $q);
            } else {
                return sprintf('(' . $titleField . ':"%s"~%d)^5 (collection_txt:"%s"~%d)', $q, $proximity, $q, $proximity);
            }
        }
    }

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
    protected function nativeSearch($q, $args = [], $rows = 20, $searchTags = false, $proximity = 10000000, $page = 1)
    {
        if (!empty($q)) {
            $query = $this->createSolrQuery($args, $rows, $page);
            $queryTxt = $this->buildQuery($q, $args, $searchTags, $proximity);


            $query->setQuery($queryTxt);

            /*
             * Only need these fields as Doctrine
             * will do the rest.
             */
            $query->addFields([
                'id',
                'document_type_s',
                SolariumNodeSource::IDENTIFIER_KEY,
                'node_name_s',
                'locale_s',
            ]);

            if (null !== $this->logger) {
                $this->logger->debug('[Solr] Request node-sources search…', [
                    'query' => $queryTxt,
                    'params' => $query->getParams(),
                ]);
            }

            $solrRequest = $this->client->select($query);
            return json_decode($solrRequest->getResponse()->getBody(), true);
        } else {
            return null;
        }
    }

    /**
     * @param $args
     * @return mixed
     */
    protected function argFqProcess(&$args)
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

        /*
         * Filter by translation or locale
         */
        if (isset($args['translation']) && $args['translation'] instanceof Translation) {
            $args["fq"][] = "locale_s:" . $args['translation']->getLocale();
        }
        if (isset($args['locale']) && is_string($args['locale'])) {
            $args["fq"][] = "locale_s:" . $args['locale'];
        }

        return $args;
    }

    /**
     * @return string
     */
    protected function getDocumentType()
    {
        return 'NodesSources';
    }

    /**
     * @param $response
     * @return array
     */
    protected function parseSolrResponse($response)
    {
        if (null !== $response) {
            $doc = array_map(
                function ($n) use ($response) {
                    if (isset($response["highlighting"])) {
                        return [
                            "nodeSource" => $this->em->find(
                                NodesSources::class,
                                (int) $n[SolariumNodeSource::IDENTIFIER_KEY]
                            ),
                            "highlighting" => $response["highlighting"][$n['id']],
                        ];
                    }
                    return $this->em->find(
                        NodesSources::class,
                        $n[SolariumNodeSource::IDENTIFIER_KEY]
                    );
                },
                $response['response']['docs']
            );

            return $doc;
        }

        return [];
    }
}
