<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\SearchEngine;

use Psr\Log\LoggerInterface;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Events\NodesSources\NodesSourcesIndexingEvent;
use RZ\Roadiz\Markdown\MarkdownInterface;
use Solarium\Client;
use Solarium\QueryType\Update\Query\Query;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Wrap a Solarium and a NodeSource together to ease indexing.
 */
class SolariumNodeSource extends AbstractSolarium
{
    const DOCUMENT_TYPE = 'NodesSources';
    const IDENTIFIER_KEY = 'node_source_id_i';

    protected $nodeSource = null;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;
    /**
     * @var Client
     */
    protected $client;
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param NodesSources             $nodeSource
     * @param Client                   $client
     * @param EventDispatcherInterface $dispatcher
     * @param LoggerInterface|null     $logger
     * @param MarkdownInterface|null   $markdown
     */
    public function __construct(
        NodesSources $nodeSource,
        Client $client,
        EventDispatcherInterface $dispatcher,
        LoggerInterface $logger = null,
        MarkdownInterface $markdown = null
    ) {
        parent::__construct($client, $logger, $markdown);
        $this->nodeSource = $nodeSource;
        $this->dispatcher = $dispatcher;
    }

    public function getDocumentId()
    {
        return $this->nodeSource->getId();
    }

    /**
     * Get a key/value array representation of current node-source document.
     *
     * @param bool $subResource Tell when this field gathering is for a main resource indexation or a sub-resource
     *
     * @return array
     * @throws \Exception
     */
    public function getFieldsAssoc(bool $subResource = false): array
    {
        $event = new NodesSourcesIndexingEvent($this->nodeSource, [], $this);

        return $this->dispatcher->dispatch($event)->getAssociations();
    }

    /**
     * Remove any document linked to current node-source.
     *
     * @param Query $update
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
