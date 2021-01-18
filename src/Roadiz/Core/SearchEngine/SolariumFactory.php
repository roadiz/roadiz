<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\SearchEngine;

use Psr\Log\LoggerInterface;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Entities\DocumentTranslation;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Exceptions\SolrServerNotConfiguredException;
use RZ\Roadiz\Core\Handlers\HandlerFactoryInterface;
use RZ\Roadiz\Markdown\MarkdownInterface;
use Solarium\Client;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class SolariumFactory implements SolariumFactoryInterface
{
    /**
     * @var Client|null
     */
    protected $solr;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var MarkdownInterface
     */
    protected $markdown;
    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;
    /**
     * @var HandlerFactoryInterface
     */
    protected $handlerFactory;

    /**
     * @param Client|null              $solr
     * @param LoggerInterface          $logger
     * @param MarkdownInterface        $markdown
     * @param EventDispatcherInterface $dispatcher
     * @param HandlerFactoryInterface  $handlerFactory
     */
    public function __construct(
        ?Client $solr,
        LoggerInterface $logger,
        MarkdownInterface $markdown,
        EventDispatcherInterface $dispatcher,
        HandlerFactoryInterface $handlerFactory
    ) {
        $this->solr = $solr;
        $this->logger = $logger;
        $this->markdown = $markdown;
        $this->dispatcher = $dispatcher;
        $this->handlerFactory = $handlerFactory;
    }

    public function createWithDocument(Document $document): SolariumDocument
    {
        if (null === $this->solr) {
            throw new SolrServerNotConfiguredException();
        }
        return new SolariumDocument(
            $document,
            $this,
            $this->solr,
            $this->logger,
            $this->markdown
        );
    }

    public function createWithDocumentTranslation(DocumentTranslation $documentTranslation): SolariumDocumentTranslation
    {
        if (null === $this->solr) {
            throw new SolrServerNotConfiguredException();
        }
        return new SolariumDocumentTranslation(
            $documentTranslation,
            $this->solr,
            $this->logger,
            $this->markdown
        );
    }

    public function createWithNodesSources(NodesSources $nodeSource): SolariumNodeSource
    {
        if (null === $this->solr) {
            throw new SolrServerNotConfiguredException();
        }
        return new SolariumNodeSource(
            $nodeSource,
            $this->solr,
            $this->dispatcher,
            $this->logger,
            $this->markdown
        );
    }
}
