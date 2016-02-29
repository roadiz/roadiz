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


use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Exceptions\SolrServerNotConfiguredException;
use Solarium\Client;
use Solarium\QueryType\Update\Query\Document\DocumentInterface;
use Solarium\QueryType\Update\Query\Query;
use \Parsedown;

/**
 * Wrap a Solarium and a NodeSource together to ease indexing.
 */
class SolariumNodeSource
{
    const DOCUMENT_TYPE = 'NodesSources';
    const IDENTIFIER_KEY = 'node_source_id_i';

    protected $client = null;
    protected $indexed = false;
    protected $nodeSource = null;
    protected $document = null;

    /**
     * Create a new SolariumNodeSource.
     *
     * @param NodesSources     $nodeSource
     * @param \Solarium_Client $client
     *
     * @throws \RZ\Roadiz\Core\Exceptions\SolrServerNotConfiguredException If Solr server does not respond.
     */
    public function __construct($nodeSource, Client $client = null)
    {
        if (null === $client) {
            throw new SolrServerNotConfiguredException("No Solr server available", 1);
        }

        $this->client = $client;
        $this->nodeSource = $nodeSource;
    }

    /**
     * Get document fron Solr index.
     *
     * @return boolean *FALSE* if no document found linked to current node-source.
     */
    public function getDocumentFromIndex()
    {
        $query = $this->client->createSelect();
        $query->setQuery(static::IDENTIFIER_KEY . ':' . $this->nodeSource->getId());
        $query->createFilterQuery('type')->setQuery('document_type_s:' . static::DOCUMENT_TYPE);

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
    }

    /**
     * Index current document with nodeSource data.
     *
     * @return boolean
     *
     * @throws \RuntimeException If no document is available.
     */
    public function index()
    {
        if (null !== $this->document) {
            $this->document->id = uniqid(); //or something else suitably unique

            foreach ($this->getFieldsAssoc() as $key => $value) {
                $this->document->$key = $value;
            }

            return true;
        } else {
            throw new \RuntimeException("No Solr document available for current NodeSource", 1);
        }
    }

    /**
     * Get a key/value array representation of current node-source document.
     *
     * @return array
     */
    public function getFieldsAssoc()
    {
        $assoc = [];
        $collection = [];
        $node = $this->nodeSource->getNode();

        if (null === $node) {
            throw new \Exception("No node relation found for source: " . $this->nodeSource->getTitle(), 1);
        }

        // Need a documentType field
        $assoc['document_type_s'] = static::DOCUMENT_TYPE;
        // Need a nodeSourceId field
        $assoc[static::IDENTIFIER_KEY] = $this->nodeSource->getId();
        $assoc['node_type_s'] = $node->getNodeType()->getName();
        $assoc['node_name_s'] = $node->getNodeName();
        $assoc['node_status_i'] = $node->getStatus();
        $assoc['node_visible_b'] = $node->isVisible();

        // Need a locale field
        $assoc['locale_s'] = $this->nodeSource->getTranslation()->getLocale();
        $out = array_map(
            function (Tag $x) {
                return $x->getTranslatedTags()->first()->getName();
            },
            $this->nodeSource->getHandler()->getTags()
        );
        $assoc['tags_txt'] = $out;

        $assoc['title'] = $this->nodeSource->getTitle();
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

            if ('content' == $name) {
                $assoc['content'] = $content;
            } else {
                /*
                 * Use locale to create field name
                 * with right language
                 */
                $name .= '_t';
                $assoc[$name] = $content;
            }

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
     * Index current nodeSource and commit after.
     *
     * Use this method only when you need to index single NodeSources.
     *
     * @return boolean
     */
    public function indexAndCommit()
    {
        $update = $this->client->createUpdate();

        $this->setDocument($update->createDocument());

        if (true === $this->index()) {
            // add the documents and a commit command to the update query
            $update->addDocument($this->getDocument());
            $update->addCommit();

            return $this->client->update($update);
        }

        return false;
    }

    /**
     * Update current nodeSource document and commit after.
     *
     * Use this method **only** when you need to re-index a single NodeSources.
     *
     * @return boolean
     */
    public function updateAndCommit()
    {
        $update = $this->client->createUpdate();
        $this->update($update);
        $update->addCommit();
        return $this->client->update($update);
    }

    /**
     * Update current nodeSource document with existing update.
     *
     * Use this method only when you need to re-index bulk NodeSources.
     *
     * @param  Query  $update
     */
    public function update(Query $update)
    {
        $update->addDeleteById($this->document->id);
        $this->setDocument($update->createDocument());
        $this->index();
        // add the document to the update query
        $update->addDocument($this->document);
    }

    /**
     * Remove any document linked to current node-source.
     *
     * @param \Solarium\QueryType\Update\Query\Query $update
     *
     * @return boolean
     */
    public function clean(Query $update)
    {
        $update->addDeleteQuery(
            static::IDENTIFIER_KEY . ':"' . $this->nodeSource->getId() . '"' .
            '&document_type_s:"' . static::DOCUMENT_TYPE . '"' .
            '&locale_s:"' . $this->nodeSource->getTranslation()->getLocale() . '"'
        );

        return true;
    }

    /**
     * Remove current document from SearchEngine index.
     *
     * @param \Solarium\QueryType\Update\Query\Query $update
     *
     * @return boolean
     *
     * @throws \RuntimeException If no document is available.
     */
    public function remove(Query $update)
    {
        if (null !== $this->document) {
            $update->addDeleteById($this->document->id);

            return true;
        } else {
            return false;
        }
    }

    /**
     * Remove current Solr document and commit after.
     *
     * Use this method only when you need to remove a single NodeSources.
     */
    public function removeAndCommit()
    {
        $update = $this->client->createUpdate();

        if (true === $this->remove($update)) {
            $update->addCommit();
            $this->client->update($update);
        }
    }
    /**
     * Remove any document linked to current node-source and commit after.
     *
     * Use this method only when you need to remove a single NodeSources.
     */
    public function cleanAndCommit()
    {
        $update = $this->client->createUpdate();

        if (true === $this->clean($update)) {
            $update->addCommit();
            $this->client->update($update);
        }
    }

    /**
     * @param \Solarium\QueryType\Update\Query\Document\DocumentInterface $document
     */
    public function setDocument(DocumentInterface $document)
    {
        $this->document = $document;
    }
    /**
     * @return \Solarium\QueryType\Update\Query\Document\DocumentInterface
     */
    public function getDocument()
    {
        return $this->document;
    }
}
