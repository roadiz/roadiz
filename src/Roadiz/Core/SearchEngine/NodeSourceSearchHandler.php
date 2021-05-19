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
 * Class NodeSourceSearchHandler
 * @package RZ\Roadiz\Core\SearchEngine
 */
class NodeSourceSearchHandler extends AbstractSearchHandler
{
    /**
     * @var bool
     */
    protected $boostByPublicationDate = false;
    /**
     * @var bool
     */
    protected $boostByUpdateDate = false;
    /**
     * @var bool
     */
    protected $boostByCreationDate = false;

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
            $query->setFields([
                'score',
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

            $solrRequest = $this->client->execute($query);
            return $solrRequest->getData();
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
        if (isset($args['visible'])) {
            $tmp = "node_visible_b:" . (($args['visible']) ? 'true' : 'false');
            unset($args['visible']);
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

        /*
         * Filter by parent node
         */
        if (!empty($args['parent'])) {
            if ($args['parent'] instanceof Node) {
                $args["fq"][] = "node_parent_i:" . $args['parent']->getId();
            } elseif (is_string($args['parent'])) {
                $args["fq"][] = "node_parent_s:" . trim($args['parent']);
            } elseif (is_numeric($args['parent'])) {
                $args["fq"][] = "node_parent_i:" . (int) $args['parent'];
            }
            unset($args['parent']);
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
