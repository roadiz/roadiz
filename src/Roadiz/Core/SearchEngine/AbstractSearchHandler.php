<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\SearchEngine;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use RZ\Roadiz\Core\Entities\Translation;
use Solarium\Core\Client\Client;
use Solarium\Core\Query\Helper;
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
     * @var int
     */
    protected $highlightingFragmentSize = 150;

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
     * @param array|null $response
     * @return array
     * @deprecated Use SolrSearchResults DTO
     */
    abstract protected function parseSolrResponse($response);

    /**
     * @param array $args
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
     * @param string $q
     *
     * @return bool
     */
    protected function isQuerySingleWord(string $q): bool
    {
        return preg_match('#[\s\-\'\"\–\—\’\”\‘\“\/\+\.\,]#', $q) !== 1;
    }

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
        $singleWord = $this->isQuerySingleWord($q);
        $titleField = $this->getTitleField($args);
        /*
         * Search in node-sources tags name…
         */
        if ($searchTags) {
            /*
             * @see http://www.solrtutorial.com/solr-query-syntax.html
             */
            if ($singleWord) {
                // Need to use wildcard BEFORE and AFTER
                return sprintf('(' . $titleField . ':*%s*)^10 (collection_txt:*%s*) (tags_txt:*%s*)', $q, $q, $q);
            } else {
                return sprintf('(' . $titleField . ':"%s"~%d)^10 (collection_txt:"%s"~%d) (tags_txt:"%s"~%d)', $q, $proximity, $q, $proximity, $q, $proximity);
            }
        } else {
            if ($singleWord) {
                // Need to use wildcard BEFORE and AFTER
                return sprintf('(' . $titleField . ':*%s*)^10 (collection_txt:*%s*)', $q, $q);
            } else {
                return sprintf('(' . $titleField . ':"%s"~%d)^10 (collection_txt:"%s"~%d)', $q, $proximity, $q, $proximity);
            }
        }
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
     * @return SolrSearchResults Return a SolrSearchResults iterable object.
     * [document, highlighting] for Documents and [nodeSource, highlighting]
     */
    public function searchWithHighlight(
        $q,
        $args = [],
        $rows = 20,
        $searchTags = false,
        $proximity = 10000000,
        $page = 1
    ): SolrSearchResults {
        $args = $this->argFqProcess($args);
        $args["fq"][] = "document_type_s:" . $this->getDocumentType();
        $args = array_merge($this->getHighlightingOptions(), $args);
        $response = $this->nativeSearch($q, $args, $rows, $searchTags, $proximity, $page);
        return new SolrSearchResults(null !== $response ? $response : [], $this->em);
    }

    /**
     * @return array
     */
    protected function getHighlightingOptions(): array
    {
        $tmp = [];
        $tmp["hl"] = true;
        $tmp["hl.fl"] = "collection_txt";
        $tmp["hl.fragsize"] = $this->getHighlightingFragmentSize();
        $tmp["hl.simple.pre"] = '<span class="solr-highlight">';
        $tmp["hl.simple.post"] = '</span>';

        return $tmp;
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
     * @return SolrSearchResults Return an array of doctrine Entities (Document, NodesSources)
     */
    public function search(
        $q,
        $args = [],
        $rows = 20,
        $searchTags = false,
        $proximity = 10000000,
        $page = 1
    ): SolrSearchResults {
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
}
