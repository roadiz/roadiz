<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\SearchEngine;

use Doctrine\Common\Collections\Collection;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Entities\Translation;

/**
 * @package RZ\Roadiz\Core\SearchEngine
 */
class NodeSourceSearchHandler extends AbstractSearchHandler implements NodeSourceSearchHandlerInterface
{
    protected bool $boostByPublicationDate = false;
    protected bool $boostByUpdateDate = false;
    protected bool $boostByCreationDate = false;

    /**
     * @param string  $q
     * @param array   $args
     * @param integer $rows
     * @param boolean $searchTags
     * @param integer $proximity Proximity matching: Lucene supports finding words are a within a specific distance away.
     * @param integer $page
     *
     * @return array|null
     */
    protected function nativeSearch($q, $args = [], $rows = 20, $searchTags = false, $proximity = 10000000, $page = 1)
    {
        if (!empty($q)) {
            $query = $this->createSolrQuery($args, $rows, $page);
            $queryTxt = $this->buildQuery($q, $args, $searchTags, $proximity);

            if ($this->boostByPublicationDate) {
                $boost = '{!boost b=recip(ms(NOW,published_at_dt),3.16e-11,1,1)}';
                $queryTxt = $boost . $queryTxt;
            }
            if ($this->boostByUpdateDate) {
                $boost = '{!boost b=recip(ms(NOW,updated_at_dt),3.16e-11,1,1)}';
                $queryTxt = $boost . $queryTxt;
            }
            if ($this->boostByCreationDate) {
                $boost = '{!boost b=recip(ms(NOW,created_at_dt),3.16e-11,1,1)}';
                $queryTxt = $boost . $queryTxt;
            }

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
     * @param array $args
     * @return mixed
     */
    protected function argFqProcess(&$args)
    {
        if (!isset($args["fq"])) {
            $args["fq"] = [];
        }

        $visible = $args['visible'] ?? $args['node.visible'] ?? null;
        if (isset($visible)) {
            $tmp = "node_visible_b:" . (($visible) ? 'true' : 'false');
            unset($args['visible']);
            unset($args['node.visible']);
            $args["fq"][] = $tmp;
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

        /*
         * Filter by Node type
         */
        $nodeType = $args['nodeType'] ?? $args['node.nodeType'] ?? null;
        if (!empty($nodeType)) {
            if (is_array($nodeType) || $nodeType instanceof Collection) {
                $orQuery = [];
                foreach ($nodeType as $nodeType) {
                    if ($nodeType instanceof NodeType) {
                        $orQuery[] = $nodeType->getName();
                    } else {
                        $orQuery[] = $nodeType;
                    }
                }
                $args["fq"][] = "node_type_s:(" . implode(' OR ', $orQuery) . ')';
            } elseif ($nodeType instanceof NodeType) {
                $args["fq"][] = "node_type_s:" . $nodeType->getName();
            } else {
                $args["fq"][] = "node_type_s:" . $nodeType;
            }
            unset($args['nodeType']);
            unset($args['node.nodeType']);
        }

        /*
         * Filter by parent node
         */
        $parent = $args['parent'] ?? $args['node.parent'] ?? null;
        if (!empty($parent)) {
            if ($parent instanceof Node) {
                $args["fq"][] = "node_parent_i:" . $parent->getId();
            } elseif (is_string($parent)) {
                $args["fq"][] = "node_parent_s:" . trim($parent);
            } elseif (is_numeric($parent)) {
                $args["fq"][] = "node_parent_i:" . (int) $parent;
            }
            unset($args['parent']);
            unset($args['node.parent']);
        }

        /*
         * Handle publication date-time filtering
         */
        if (isset($args['publishedAt'])) {
            $tmp = "published_at_dt:";
            if (!is_array($args['publishedAt']) && $args['publishedAt'] instanceof \DateTime) {
                $tmp .= $args['publishedAt']->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');
            } elseif (isset($args['publishedAt'][0]) &&
                $args['publishedAt'][0] === "<=" &&
                isset($args['publishedAt'][1]) &&
                $args['publishedAt'][1] instanceof \DateTime) {
                $tmp .= "[* TO " . $args['publishedAt'][1]->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z') . "]";
            } elseif (isset($args['publishedAt'][0]) &&
                $args['publishedAt'][0] === ">=" &&
                isset($args['publishedAt'][1]) &&
                $args['publishedAt'][1] instanceof \DateTime) {
                $tmp .= "[" . $args['publishedAt'][1]->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z') . " TO *]";
            }
            unset($args['publishedAt']);
            $args["fq"][] = $tmp;
        }

        $status = $args['status'] ?? $args['node.status'] ?? null;
        if (isset($status)) {
            $tmp = "node_status_i:";
            if (!is_array($status)) {
                $tmp .= (string) $status;
            } elseif ($status[0] == "<=") {
                $tmp .= "[* TO " . (string) $status[1] . "]";
            } elseif ($status[0] == ">=") {
                $tmp .= "[" . (string) $status[1] . " TO *]";
            }
            unset($args['status']);
            unset($args['node.status']);
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
     * @param array|null $response
     * @return array
     * @deprecated Use SolrSearchResults DTO
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

    /**
     * @return NodeSourceSearchHandler
     */
    public function boostByPublicationDate(): NodeSourceSearchHandler
    {
        $this->boostByPublicationDate = true;
        $this->boostByUpdateDate = false;
        $this->boostByCreationDate = false;

        return $this;
    }

    /**
     * @return NodeSourceSearchHandler
     */
    public function boostByUpdateDate(): NodeSourceSearchHandler
    {
        $this->boostByPublicationDate = false;
        $this->boostByUpdateDate = true;
        $this->boostByCreationDate = false;

        return $this;
    }

    /**
     * @return NodeSourceSearchHandler
     */
    public function boostByCreationDate(): NodeSourceSearchHandler
    {
        $this->boostByPublicationDate = false;
        $this->boostByUpdateDate = false;
        $this->boostByCreationDate = true;

        return $this;
    }
}
