<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\SearchEngine\Subscriber;

use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Entities\Folder;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Events\DocumentDeletedEvent;
use RZ\Roadiz\Core\Events\DocumentFileUploadedEvent;
use RZ\Roadiz\Core\Events\DocumentInFolderEvent;
use RZ\Roadiz\Core\Events\DocumentOutFolderEvent;
use RZ\Roadiz\Core\Events\DocumentTranslationUpdatedEvent;
use RZ\Roadiz\Core\Events\DocumentUpdatedEvent;
use RZ\Roadiz\Core\Events\FilterDocumentEvent;
use RZ\Roadiz\Core\Events\FilterNodeEvent;
use RZ\Roadiz\Core\Events\Folder\FolderUpdatedEvent;
use RZ\Roadiz\Core\Events\Node\NodeCreatedEvent;
use RZ\Roadiz\Core\Events\Node\NodeDeletedEvent;
use RZ\Roadiz\Core\Events\Node\NodeTaggedEvent;
use RZ\Roadiz\Core\Events\Node\NodeUndeletedEvent;
use RZ\Roadiz\Core\Events\Node\NodeUpdatedEvent;
use RZ\Roadiz\Core\Events\Node\NodeVisibilityChangedEvent;
use RZ\Roadiz\Core\Events\NodesSources\NodesSourcesDeletedEvent;
use RZ\Roadiz\Core\Events\NodesSources\NodesSourcesUpdatedEvent;
use RZ\Roadiz\Core\Events\Tag\TagUpdatedEvent;
use RZ\Roadiz\Core\SearchEngine\Message\SolrDeleteMessage;
use RZ\Roadiz\Core\SearchEngine\Message\SolrReindexMessage;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Workflow\Event\Event;

/**
 * Subscribe to Node and NodesSources event to update
 * a Solr server documents.
 */
class SolariumSubscriber implements EventSubscriberInterface
{
    protected MessageBusInterface $messageBus;

    /**
     * @param MessageBusInterface $messageBus
     */
    public function __construct(
        MessageBusInterface $messageBus
    ) {
        $this->messageBus = $messageBus;
    }

    public static function getSubscribedEvents()
    {
        return [
            NodeUpdatedEvent::class => 'onSolariumNodeUpdate',
            'workflow.node.completed' => ['onSolariumNodeWorkflowComplete'],
            NodeVisibilityChangedEvent::class => 'onSolariumNodeUpdate',
            NodesSourcesUpdatedEvent::class => 'onSolariumSingleUpdate',
            NodesSourcesDeletedEvent::class => 'onSolariumSingleDelete',
            NodeDeletedEvent::class => 'onSolariumNodeDelete',
            NodeUndeletedEvent::class => 'onSolariumNodeUpdate',
            NodeTaggedEvent::class => 'onSolariumNodeUpdate',
            NodeCreatedEvent::class => 'onSolariumNodeUpdate',
            TagUpdatedEvent::class => 'onSolariumTagUpdate', // Possibly too greedy if lots of nodes tagged
            DocumentFileUploadedEvent::class => 'onSolariumDocumentUpdate',
            DocumentTranslationUpdatedEvent::class => 'onSolariumDocumentUpdate',
            DocumentInFolderEvent::class => 'onSolariumDocumentUpdate',
            DocumentOutFolderEvent::class => 'onSolariumDocumentUpdate',
            DocumentUpdatedEvent::class => 'onSolariumDocumentUpdate',
            DocumentDeletedEvent::class => 'onSolariumDocumentDelete',
            FolderUpdatedEvent::class => 'onSolariumFolderUpdate', // Possibly too greedy if lots of docs tagged
        ];
    }

    /**
     * @param Event $event
     */
    public function onSolariumNodeWorkflowComplete(Event $event): void
    {
        $node = $event->getSubject();
        if ($node instanceof Node && null !== $node->getId()) {
            $this->messageBus->dispatch(new Envelope(new SolrReindexMessage(Node::class, $node->getId())));
        }
    }

    /**
     * Update or create Solr document for current Node-source.
     *
     * @param NodesSourcesUpdatedEvent $event
     *
     * @throws \Exception
     */
    public function onSolariumSingleUpdate(NodesSourcesUpdatedEvent $event)
    {
        $id = $event->getNodeSource()->getId();
        if (null !== $id) {
            $this->messageBus->dispatch(new Envelope(new SolrReindexMessage(NodesSources::class, $id)));
        }
    }

    /**
     * Delete solr document for current Node-source.
     *
     * @param NodesSourcesDeletedEvent $event
     */
    public function onSolariumSingleDelete(NodesSourcesDeletedEvent $event)
    {
        $id = $event->getNodeSource()->getId();
        if (null !== $id) {
            $this->messageBus->dispatch(new Envelope(new SolrDeleteMessage(NodesSources::class, $id)));
        }
    }

    /**
     * Delete solr documents for each Node sources.
     *
     * @param NodeDeletedEvent $event
     */
    public function onSolariumNodeDelete(NodeDeletedEvent $event)
    {
        $id = $event->getNode()->getId();
        if (null !== $id) {
            $this->messageBus->dispatch(new Envelope(new SolrDeleteMessage(Node::class, $id)));
        }
    }

    /**
     * Update or create solr documents for each Node sources.
     *
     * @param FilterNodeEvent $event
     *
     * @throws \Exception
     */
    public function onSolariumNodeUpdate(FilterNodeEvent $event)
    {
        $id = $event->getNode()->getId();
        if (null !== $id) {
            $this->messageBus->dispatch(new Envelope(new SolrReindexMessage(Node::class, $id)));
        }
    }


    /**
     * Delete solr documents for each Document translation.
     *
     * @param FilterDocumentEvent $event
     */
    public function onSolariumDocumentDelete(FilterDocumentEvent $event)
    {
        $document = $event->getDocument();
        if ($document instanceof Document && null !== $document->getId()) {
            $this->messageBus->dispatch(new Envelope(new SolrDeleteMessage(Document::class, $document->getId())));
        }
    }

    /**
     * Update or create solr documents for each Document translation.
     *
     * @param FilterDocumentEvent $event
     *
     * @throws \Exception
     */
    public function onSolariumDocumentUpdate(FilterDocumentEvent $event)
    {
        $document = $event->getDocument();
        if ($document instanceof Document && null !== $document->getId()) {
            $this->messageBus->dispatch(new Envelope(new SolrReindexMessage(Document::class, $document->getId())));
        }
    }

    /**
     * Update solr documents linked to current event Tag.
     *
     * @param TagUpdatedEvent $event
     *
     * @throws \Exception
     * @deprecated This can lead to a timeout if more than 500 nodes use that tag!
     */
    public function onSolariumTagUpdate(TagUpdatedEvent $event)
    {
        $id = $event->getTag()->getId();
        if (null !== $id) {
            $this->messageBus->dispatch(new Envelope(new SolrReindexMessage(Tag::class, $id)));
        }
    }

    /**
     * Update solr documents linked to current event Folder.
     *
     * @param FolderUpdatedEvent $event
     *
     * @throws \Exception
     * @deprecated This can lead to a timeout if more than 500 documents use that folder!
     */
    public function onSolariumFolderUpdate(FolderUpdatedEvent $event)
    {
        $id = $event->getFolder()->getId();
        if (null !== $id) {
            $this->messageBus->dispatch(new Envelope(new SolrReindexMessage(Folder::class, $id)));
        }
    }
}
