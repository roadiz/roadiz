<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\SearchEngine;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use RZ\Roadiz\Core\Entities\Translation;
use Solarium\Core\Client\Client;
use Solarium\Core\Query\Helper;
use Solarium\QueryType\Select\Query\Query;

abstract class AbstractSearchHandler implements SearchHandlerInterface
{
    protected ?Client $client = null;
    protected ?EntityManagerInterface $em = null;
    protected ?LoggerInterface $logger = null;
    protected int $highlightingFragmentSize = 150;

    /**
     * @param Client $client
     * @param EntityManagerInterface $em
     * @param LoggerInterface|null $logger
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
     * @return SearchResultsInterface Return a SearchResultsInterface iterable object.
     */
    public function searchWithHighlight(
        $q,
        $args = [],
        $rows = 20,
        $searchTags = false,
        $proximity = 10000000,
        $page = 1
    ): SearchResultsInterface {
        $args = $this->argFqProcess($args);
        $args["fq"][] = "document_type_s:" . $this->getDocumentType();
        $args = array_merge($this->getHighlightingOptions($args), $args);
        $response = $this->nativeSearch($q, $args, $rows, $searchTags, $proximity, $page);
        return new SolrSearchResults(null !== $response ? $response : [], $this->em);
    }

    /**
     * @param array $args
     * @return mixed
     */
    abstract protected function argFqProcess(&$args);

    /**
     * @return string
     */
    abstract protected function getDocumentType();

    /**
     * @return array
     */
    protected function getHighlightingOptions(array &$args = []): array
    {
        $tmp = [];
        $tmp["hl"] = true;
        $tmp["hl.fl"] = $this->getCollectionField($args);
        $tmp["hl.fragsize"] = $this->getHighlightingFragmentSize();
        $tmp["hl.simple.pre"] = '<span class="solr-highlight">';
        $tmp["hl.simple.post"] = '</span>';

        return $tmp;
    }

    /**
     * @param array $args
     *
     * @return string
     */
    protected function getCollectionField(array &$args): string
    {
        /*
         * Use collection_txt_LOCALE when search
         * is filtered by translation.
         */
        if (isset($args['locale']) && is_string($args['locale'])) {
            return 'collection_txt_' . \Locale::getPrimaryLanguage($args['locale']);
        }
        if (isset($args['translation']) && $args['translation'] instanceof Translation) {
            return 'collection_txt_' . \Locale::getPrimaryLanguage($args['translation']->getLocale());
        }
        return 'collection_txt';
    }

    /**
     * @return int
     */
    public function getHighlightingFragmentSize(): int
    {
        return $this->highlightingFragmentSize;
    }

    /**
     * @param int $highlightingFragmentSize
     *
     * @return AbstractSearchHandler
     */
    public function setHighlightingFragmentSize(int $highlightingFragmentSize): AbstractSearchHandler
    {
        $this->highlightingFragmentSize = $highlightingFragmentSize;

        return $this;
    }

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
    abstract protected function nativeSearch($q, $args = [], $rows = 20, $searchTags = false, $proximity = 10000000, $page = 1);

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
     * @return SearchResultsInterface Return an array of doctrine Entities (Document, NodesSources)
     */
    public function search(
        $q,
        $args = [],
        $rows = 20,
        $searchTags = false,
        $proximity = 10000000,
        $page = 1
    ): SearchResultsInterface {
        $args = $this->argFqProcess($args);
        $args["fq"][] = "document_type_s:" . $this->getDocumentType();
        $tmp = [];
        $args = array_merge($tmp, $args);

        $response = $this->nativeSearch($q, $args, $rows, $searchTags, $proximity, $page);
        return new SolrSearchResults(null !== $response ? $response : [], $this->em);
    }

    /**
     * @param string $q
     * @param array $args
     * @param int $rows Useless var but keep it for retro-compatibility
     * @param bool $searchTags
     * @param int $proximity Proximity matching: Lucene supports finding words are a within a specific distance away. Default 10000000
     * @return int
     * @deprecated Use SolrSearchResults DTO
     */
    public function count($q, $args = [], $rows = 0, $searchTags = false, $proximity = 10000000)
    {
        $args = $this->argFqProcess($args);
        $args["fq"][] = "document_type_s:" . $this->getDocumentType();
        $tmp = [];
        $args = array_merge($tmp, $args);
        $response = $this->nativeSearch($q, $args, $rows, $searchTags, $proximity);
        return null !== $response ? $this->parseResultCount($response) : 0;
    }

    /**
     * @param array $response
     * @return int
     * @deprecated Use SolrSearchResults DTO
     */
    protected function parseResultCount($response)
    {
        if (null !== $response && isset($response['response']['numFound'])) {
            return (int) $response['response']['numFound'];
        }

        return 0;
    }

    /**
     * @param array|null $response
     * @return array
     * @deprecated Use SolrSearchResults DTO
     */
    abstract protected function parseSolrResponse($response);

    /**
     * Default Solr query builder.
     *
     * Extends this method to customize your Solr queries. Eg. to boost custom fields.
     *
     * @param string|null $q
     * @param array $args
     * @param bool $searchTags
     * @param int $proximity
     * @return string
     */
    protected function buildQuery($q, array &$args, $searchTags = false, $proximity = 10000000)
    {
        $q = null !== $q ? trim($q) : '';
        $qHelper = new Helper();
        $singleWord = $this->isQuerySingleWord($q);
        $titleField = $this->getTitleField($args);
        $collectionField = $this->getCollectionField($args);
        $tagsField = $this->getTagsField($args);

        /**
         * Generate a fuzzy query by appending proximity to each word
         * @see https://lucene.apache.org/solr/guide/6_6/the-standard-query-parser.html#TheStandardQueryParser-FuzzySearches
         */
        $words = preg_split('#[\s,]+#', $q, -1, PREG_SPLIT_NO_EMPTY);
        $fuzzyiedQuery = implode(' ', array_map(function (string $word) use ($proximity, $qHelper) {
            /*
             * Do not fuzz short words: Solr crashes
             */
            if (strlen($word) > 3) {
                return $qHelper->escapeTerm($word) . '~' . $proximity;
            }
            return $qHelper->escapeTerm($word);
        }, $words));
        /*
         * Only escape exact query
         */
        $exactQuery = $qHelper->escapeTerm($q);
        if (!$singleWord) {
            /*
             * adds quotes if multi word exact query
             */
            $exactQuery = '"' . $exactQuery . '"';
        }

        /*
         * Search in node-sources tags name…
         */
        if ($searchTags) {
            // Need to use Fuzzy search AND Exact search
            return sprintf(
                '(' . $titleField . ':%s)^10 (' . $titleField . ':%s) (' . $collectionField . ':%s)^2 (' . $collectionField . ':%s) (' . $tagsField . ':%s) (' . $tagsField . ':%s)',
                $exactQuery,
                $fuzzyiedQuery,
                $exactQuery,
                $fuzzyiedQuery,
                $exactQuery,
                $fuzzyiedQuery
            );
        } else {
            return sprintf(
                '(' . $titleField . ':%s)^10 (' . $titleField . ':%s) (' . $collectionField . ':%s)^2 (' . $collectionField . ':%s)',
                $exactQuery,
                $fuzzyiedQuery,
                $exactQuery,
                $fuzzyiedQuery
            );
        }
    }

    /**
     * @param string $q
     *
     * @return bool
     */
    protected function isQuerySingleWord(string $q): bool
    {
        return preg_match('#[\s\-\'\"\–\—\’\”\‘\“\/\+\.\,]#', $q) !== 1;
    }

    /**
     * @param array $args
     *
     * @return string
     */
    protected function getTitleField(array &$args): string
    {
        /*
         * Use title_txt_LOCALE when search
         * is filtered by translation.
         */
        if (isset($args['locale']) && is_string($args['locale'])) {
            return 'title_txt_' . \Locale::getPrimaryLanguage($args['locale']);
        }
        if (isset($args['translation']) && $args['translation'] instanceof Translation) {
            return 'title_txt_' . \Locale::getPrimaryLanguage($args['translation']->getLocale());
        }
        return 'title';
    }

    /**
     * @param array $args
     *
     * @return string
     */
    protected function getTagsField(array &$args): string
    {
        /*
         * Use tags_txt_LOCALE when search
         * is filtered by translation.
         */
        if (isset($args['locale']) && is_string($args['locale'])) {
            return 'tags_txt_' . \Locale::getPrimaryLanguage($args['locale']);
        }
        if (isset($args['translation']) && $args['translation'] instanceof Translation) {
            return 'tags_txt_' . \Locale::getPrimaryLanguage($args['translation']->getLocale());
        }
        return 'tags_txt';
    }

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
            } elseif (is_scalar($value)) {
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
}
