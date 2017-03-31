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
 * @file SolariumNodeSource.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\SearchEngine;

use Monolog\Logger;
use Parsedown;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Exceptions\SolrServerNotConfiguredException;
use Solarium\Client;
use Solarium\QueryType\Update\Query\Query;

/**
 * Wrap a Solarium and a NodeSource together to ease indexing.
 */
class SolariumNodeSource extends AbstractSolarium
{
    const DOCUMENT_TYPE = 'NodesSources';
    const IDENTIFIER_KEY = 'node_source_id_i';

    protected $nodeSource = null;

    /**
     * Create a new SolariumNodeSource.
     *
     * @param NodesSources $nodeSource
     * @param Client $client
     * @param Logger $logger
     */
    public function __construct(NodesSources $nodeSource, Client $client = null, Logger $logger = null)
    {
        if (null === $client) {
            throw new SolrServerNotConfiguredException("No Solr server available", 1);
        }

        $this->client = $client;
        $this->nodeSource = $nodeSource;
        $this->logger = $logger;
    }

    /**
     * Get document from Solr index.
     *
     * @return boolean *FALSE* if no document found linked to current node-source.
     */
    public function getDocumentFromIndex()
    {
        $query = $this->client->createSelect();
        $query->setQuery(static::IDENTIFIER_KEY . ':' . $this->nodeSource->getId());
        $query->createFilterQuery('type')->setQuery(static::TYPE_DISCRIMINATOR . ':' . static::DOCUMENT_TYPE);

        // this executes the query and returns the result
        $resultset = $this->client->select($query);

        if (0 === $resultset->getNumFound()) {
            return false;
        } else {
            foreach ($resultset as $document) {
                $this->document = $document;
                return true;
            }
        }
        return false;
    }

    /**
     * Get a key/value array representation of current node-source document.
     * @return array
     * @throws \Exception
     */
    protected function getFieldsAssoc()
    {
        $assoc = [];
        $collection = [];
        $node = $this->nodeSource->getNode();

        if (null === $node) {
            throw new \RuntimeException("No node relation found for source: " . $this->nodeSource->getTitle(), 1);
        }

        // Need a documentType field
        $assoc[static::TYPE_DISCRIMINATOR] = static::DOCUMENT_TYPE;
        // Need a nodeSourceId field
        $assoc[static::IDENTIFIER_KEY] = $this->nodeSource->getId();
        $assoc['node_type_s'] = $node->getNodeType()->getName();
        $assoc['node_name_s'] = $node->getNodeName();
        $assoc['node_status_i'] = $node->getStatus();
        $assoc['node_visible_b'] = $node->isVisible();

        // Need a locale field
        $locale = $this->nodeSource->getTranslation()->getLocale();
        $lang = \Locale::getPrimaryLanguage($locale);
        $assoc['locale_s'] = $locale;
        $out = array_map(
            function (Tag $x) {
                return $x->getTranslatedTags()->first()->getName();
            },
            $this->nodeSource->getHandler()->getTags()
        );
        // Use tags_txt to be compatible with other data types
        $assoc['tags_txt'] = $out;

        $assoc['title'] = $this->nodeSource->getTitle();
        $assoc['title_txt_' . $lang] = $this->nodeSource->getTitle();
        $collection[] = $this->nodeSource->getTitle();

        $searchableFields = $node->getNodeType()->getSearchableFields();

        /*
         * Only one content fields to search in.
         */
        foreach ($searchableFields as $field) {
            $name = $field->getName();
            $getter = $field->getGetterName();

            $content = $this->nodeSource->$getter();
            /*
             * Strip markdown syntax
             */
            $content = strip_tags(Parsedown::instance()->text($content));
            /*
             * Remove ctrl characters
             */
            $content = preg_replace("[:cntrl:]", "", $content);
            $content = preg_replace('/[\x00-\x1F]/', '', $content);

            /*
             * Use locale to create field name
             * with right language
             */
            if (in_array($lang, static::$availableLocalizedTextFields)) {
                $name .= '_txt_' . $lang;
            } else {
                $name .= '_t';
            }

            $assoc[$name] = $content;

            $collection[] = $content;
        }

        /*
         * Collect data in a single field
         * for global search
         */
        $assoc['collection_txt'] = $collection;

        return $assoc;
    }

    /**
     * Remove any document linked to current node-source.
     *
     * @param \Solarium\QueryType\Update\Query\Query $update
     * @return boolean
     */
    public function clean(Query $update)
    {
        $update->addDeleteQuery(
            static::IDENTIFIER_KEY . ':"' . $this->nodeSource->getId() . '"' .
            '&'.static::TYPE_DISCRIMINATOR.':"' . static::DOCUMENT_TYPE . '"' .
            '&locale_s:"' . $this->nodeSource->getTranslation()->getLocale() . '"'
        );

        return true;
    }
}
