<?php
declare(strict_types=1);

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

    public function getDocumentId()
    {
        return null;
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
