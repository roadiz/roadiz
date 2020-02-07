<?php
declare(strict_types=1);
/**
 * Copyright (c) 2016. Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
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
 * @file AbstractSolarium.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */
namespace RZ\Roadiz\Core\SearchEngine;

use Psr\Log\LoggerInterface;
use RZ\Roadiz\Core\Exceptions\SolrServerNotConfiguredException;
use RZ\Roadiz\Markdown\MarkdownInterface;
use Solarium\Client;
use Solarium\Core\Query\Result\Result;
use Solarium\QueryType\Update\Query\Document;
use Solarium\QueryType\Update\Query\Query;

/**
 * Class AbstractSolarium
 * @package RZ\Roadiz\Core\SearchEngine
 */
abstract class AbstractSolarium
{
    const TYPE_DISCRIMINATOR = 'document_type_s';

    public static $availableLocalizedTextFields = [
        'en',
        'ar',
        'bg',
        'ca',
        'cz',
        'da',
        'de',
        'el',
        'es',
        'eu',
        'fa',
        'fi',
        'fr',
        'ga',
        'gl',
        'hi',
        'hu',
        'hy',
        'id',
        'it',
        'ja',
        'lv',
        'nl',
        'no',
        'pt',
        'ro',
        'ru',
        'sv',
        'th',
        'tr',
    ];

    /** @var Client|null */
    protected $client = null;

    /** @var bool */
    protected $indexed = false;

    /** @var Document|null */
    protected $document = null;

    /** @var LoggerInterface|null */
    protected $logger = null;

    /** @var MarkdownInterface|null  */
    protected $markdown = null;

    /**
     * AbstractSolarium constructor.
     *
     * @param Client|null            $client
     * @param LoggerInterface|null   $logger
     * @param MarkdownInterface|null $markdown
     */
    public function __construct(?Client $client, ?LoggerInterface $logger = null, ?MarkdownInterface $markdown = null)
    {
        if (null === $client) {
            throw new SolrServerNotConfiguredException("No Solr server available", 1);
        }
        $this->client = $client;
        $this->logger = $logger;
        $this->markdown = $markdown;
    }

    /**
     * Index current nodeSource and commit after.
     *
     * Use this method only when you need to index single NodeSources.
     *
     * @return boolean|Result
     */
    public function indexAndCommit()
    {
        $update = $this->client->createUpdate();
        $this->createEmptyDocument($update);

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
     * @return Result
     */
    public function updateAndCommit()
    {
        $update = $this->client->createUpdate();
        $this->update($update);
        $update->addCommit();

        if (null !== $this->logger) {
            $this->logger->debug('[Solr] Document updated.');
        }
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
        $this->clean($update);
        $this->createEmptyDocument($update);
        $this->index();
        // add the document to the update query
        $update->addDocument($this->document);
    }

    /**
     * Remove current document from SearchEngine index.
     *
     * @param Query $update
     * @return boolean
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
     * Index current document with entity data.
     *
     * @return boolean
     * @throws \RuntimeException If no document is available.
     */
    public function index()
    {
        if (null !== $this->document) {
            $this->document->id = uniqid();

            try {
                foreach ($this->getFieldsAssoc() as $key => $value) {
                    $this->document->$key = $value;
                }
                return true;
            } catch (\RuntimeException $e) {
                return false;
            }
        } else {
            throw new \RuntimeException("No Solr item available for current entity", 1);
        }
    }

    /**
     * @return Document
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * @param Document $document
     * @return self
     * @deprecated Use createEmptyDocument instead of set an empty Solr document.
     */
    public function setDocument(Document $document)
    {
        $this->document = $document;
        return $this;
    }

    /**
     * @param Query $update
     * @return $this
     */
    public function createEmptyDocument(Query $update)
    {
        $this->document = $update->createDocument();
        return $this;
    }

    abstract public function clean(Query $update);


    /**
     * @return int|string
     */
    abstract public function getDocumentId();

    /**
     * Get document from Solr index.
     *
     * @return boolean *FALSE* if no document found linked to current node-source.
     */
    public function getDocumentFromIndex()
    {
        $query = $this->client->createSelect();
        $query->setQuery(static::IDENTIFIER_KEY . ':' . $this->getDocumentId());
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
     * Get a key/value array representation of current indexed object.
     *
     * @return array
     * @throws \Exception
     */
    abstract protected function getFieldsAssoc();

    /**
     * @param string $content
     *
     * @return string
     */
    public function cleanTextContent($content)
    {
        /*
         * Strip markdown syntax
         */
        if (null !== $this->markdown) {
            $content = strip_tags($this->markdown->textExtra($content));
        }
        /*
         * Remove ctrl characters
         */
        $content = preg_replace("[:cntrl:]", "", $content);
        $content = preg_replace('/[\x00-\x1F]/', '', $content);
        return $content;
    }
}
