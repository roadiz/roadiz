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
 * @file SolariumDocument.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */
namespace RZ\Roadiz\Core\SearchEngine;

use Psr\Log\LoggerInterface;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Markdown\MarkdownInterface;
use Solarium\Client;
use Solarium\QueryType\Update\Query\Query;

/**
 * Wrap a Solarium and a Document’ translations together to ease indexing.
 *
 * @package RZ\Roadiz\Core\SearchEngine
 */
class SolariumDocument extends AbstractSolarium
{
    /**
     * @var array
     */
    protected $documentTranslationItems;

    /**
     * @deprecated
     */
    public function getDocument()
    {
        throw new \RuntimeException('Method getDocument cannot be called for SolariumDocument.');
    }

    /**
     * @return array Each document translation Solr document
     */
    public function getDocuments()
    {
        $documents = [];
        /** @var SolariumDocumentTranslation $documentTranslationItem */
        foreach ($this->documentTranslationItems as $documentTranslationItem) {
            $documents[] = $documentTranslationItem->getDocument();
        }

        return $documents;
    }

    /**
     * SolariumDocument constructor.
     *
     * @param Document                 $rzDocument
     * @param SolariumFactoryInterface $solariumFactory
     * @param Client|null              $client
     * @param LoggerInterface|null     $logger
     * @param MarkdownInterface|null   $markdown
     */
    public function __construct(
        Document $rzDocument,
        SolariumFactoryInterface $solariumFactory,
        Client $client = null,
        LoggerInterface $logger = null,
        MarkdownInterface $markdown = null
    ) {
        parent::__construct($client, $logger, $markdown);
        $this->documentTranslationItems = [];

        foreach ($rzDocument->getDocumentTranslations() as $documentTranslation) {
            $this->documentTranslationItems[] = $solariumFactory->createWithDocumentTranslation($documentTranslation);
        }
    }

    /**
     * Get document from Solr index.
     *
     * @return boolean *FALSE* if no document found linked to current Roadiz document.
     */
    public function getDocumentFromIndex()
    {
        /** @var SolariumDocumentTranslation $documentTranslationItem */
        foreach ($this->documentTranslationItems as $documentTranslationItem) {
            $documentTranslationItem->getDocumentFromIndex();
        }

        return true;
    }

    /**
     * @param Query $update
     * @return $this
     */
    public function createEmptyDocument(Query $update)
    {
        /** @var SolariumDocumentTranslation $documentTranslationItem */
        foreach ($this->documentTranslationItems as $documentTranslationItem) {
            $documentTranslationItem->createEmptyDocument($update);
        }
        return $this;
    }

    protected function getFieldsAssoc()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function clean(Query $update)
    {
        /** @var SolariumDocumentTranslation $documentTranslationItem */
        foreach ($this->documentTranslationItems as $documentTranslationItem) {
            $documentTranslationItem->clean($update);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function indexAndCommit()
    {
        /** @var SolariumDocumentTranslation $documentTranslationItem */
        foreach ($this->documentTranslationItems as $documentTranslationItem) {
            $documentTranslationItem->indexAndCommit();
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function updateAndCommit()
    {
        /** @var SolariumDocumentTranslation $documentTranslationItem */
        foreach ($this->documentTranslationItems as $documentTranslationItem) {
            $documentTranslationItem->updateAndCommit();
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function update(Query $update)
    {
        /** @var SolariumDocumentTranslation $documentTranslationItem */
        foreach ($this->documentTranslationItems as $documentTranslationItem) {
            $documentTranslationItem->update($update);
        }
    }

    /**
     * @inheritdoc
     */
    public function remove(Query $update)
    {
        /** @var SolariumDocumentTranslation $documentTranslationItem */
        foreach ($this->documentTranslationItems as $documentTranslationItem) {
            $documentTranslationItem->remove($update);
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function removeAndCommit()
    {
        /** @var SolariumDocumentTranslation $documentTranslationItem */
        foreach ($this->documentTranslationItems as $documentTranslationItem) {
            $documentTranslationItem->removeAndCommit();
        }
    }

    /**
     * @inheritdoc
     */
    public function cleanAndCommit()
    {
        /** @var SolariumDocumentTranslation $documentTranslationItem */
        foreach ($this->documentTranslationItems as $documentTranslationItem) {
            $documentTranslationItem->cleanAndCommit();
        }
    }

    /**
     * @inheritdoc
     */
    public function index()
    {
        /** @var SolariumDocumentTranslation $documentTranslationItem */
        foreach ($this->documentTranslationItems as $documentTranslationItem) {
            $documentTranslationItem->index();
        }

        return true;
    }
}
