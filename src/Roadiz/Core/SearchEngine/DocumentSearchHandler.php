<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\SearchEngine;

use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Entities\Folder;
use RZ\Roadiz\Core\Entities\Translation;

/**
 * @package RZ\Roadiz\Core\SearchEngine
 */
class DocumentSearchHandler extends AbstractSearchHandler
{
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
            $query->setQuery($queryTxt);

            /*
             * Only need these fields as Doctrine
             * will do the rest.
             */
            $query->addFields([
                'id',
                'document_type_s',
                SolariumDocumentTranslation::IDENTIFIER_KEY,
                'filename_s',
                'locale_s',
            ]);


            if (null !== $this->logger) {
                $this->logger->debug('[Solr] Request document search…', [
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

        // filter by tag or tags
        if (!empty($args['folders'])) {
            if ($args['folders'] instanceof Folder) {
                $args["fq"][] = "tags_txt:" . $args['folders']->getTranslatedFolders()->first()->getName();
            } elseif (is_array($args['folders'])) {
                foreach ($args['folders'] as $tag) {
                    if ($tag instanceof Folder) {
                        $args["fq"][] = "tags_txt:" . $tag->getTranslatedFolders()->first()->getName();
                    }
                }
            }
            unset($args['folders']);
        }

        if (isset($args['mimeType'])) {
            $tmp = "mime_type_s:";
            if (!is_array($args['mimeType'])) {
                $tmp .= (string) $args['mimeType'];
            } else {
                $value = implode(' AND ', $args['mimeType']);
                $tmp .= '('.$value.')';
            }
            unset($args['mimeType']);
            $args["fq"][] = $tmp;
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

        /*
         * Filter by filename
         */
        if (isset($args['filename'])) {
            $args["fq"][] = "filename_s:" . trim($args['filename']);
        }

        return $args;
    }

    /**
     * @return string
     */
    protected function getDocumentType()
    {
        return 'DocumentTranslation';
    }

    /**
     * @param array|null $response
     * @return array
     */
    protected function parseSolrResponse($response)
    {
        if (null !== $response) {
            $doc = array_map(
                function ($n) use ($response) {
                    if (isset($response["highlighting"])) {
                        return [
                            "document" => $this->em
                                        ->getRepository(Document::class)
                                        ->findOneByDocumentTranslationId($n[SolariumDocumentTranslation::IDENTIFIER_KEY]),
                            "highlighting" => $response["highlighting"][$n['id']],
                        ];
                    }
                    return $this->em
                        ->getRepository(Document::class)
                        ->findOneByDocumentTranslationId($n[SolariumDocumentTranslation::IDENTIFIER_KEY]);
                },
                $response['response']['docs']
            );

            return $doc;
        }
        return [];
    }
}
