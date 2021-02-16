<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\SearchEngine\Subscriber;

use Psr\Log\LoggerInterface;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Entities\DocumentTranslation;
use RZ\Roadiz\Core\Entities\NodesSources;
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
use RZ\Roadiz\Core\Events\Node\NodeStatusChangedEvent;
use RZ\Roadiz\Core\Events\Node\NodeTaggedEvent;
use RZ\Roadiz\Core\Events\Node\NodeUndeletedEvent;
use RZ\Roadiz\Core\Events\Node\NodeUpdatedEvent;
use RZ\Roadiz\Core\Events\Node\NodeVisibilityChangedEvent;
use RZ\Roadiz\Core\Events\NodesSources\NodesSourcesDeletedEvent;
use RZ\Roadiz\Core\Events\NodesSources\NodesSourcesUpdatedEvent;
use RZ\Roadiz\Core\Events\Tag\TagUpdatedEvent;
use RZ\Roadiz\Core\SearchEngine\SolariumDocumentTranslation;
use RZ\Roadiz\Core\SearchEngine\SolariumFactoryInterface;
use RZ\Roadiz\Core\SearchEngine\SolariumNodeSource;
use Solarium\Client;
use Solarium\Exception\HttpException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribe to Node and NodesSources event to update
 * a Solr server documents.
 */
class SolariumSubscriber implements EventSubscriberInterface
{
    protected ?Client $solr;
    protected LoggerInterface $logger;
    protected SolariumFactoryInterface $solariumFactory;

    /**
     * @param Client|null              $solr
     * @param LoggerInterface          $logger
     * @param SolariumFactoryInterface $solariumFactory
     */
    public function __construct(
        ?Client $solr,
        LoggerInterface $logger,
        SolariumFactoryInterface $solariumFactory
    ) {
        $this->solr = $solr;
        $this->logger = $logger;
        $this->solariumFactory = $solariumFactory;
    }

    public static function getSubscribedEvents()
    {
        return [
            NodeUpdatedEvent::class => 'onSolariumNodeUpdate',
            NodeStatusChangedEvent::class => 'onSolariumNodeUpdate',
            NodeVisibilityChangedEvent::class => 'onSolariumNodeUpdate',
            NodesSourcesUpdatedEvent::class => 'onSolariumSingleUpdate',
            NodesSourcesDeletedEvent::class => 'onSolariumSingleDelete',
            NodeDeletedEvent::class => 'onSolariumNodeDelete',
            NodeUndeletedEvent::class => 'onSolariumNodeUpdate',
            NodeTaggedEvent::class => 'onSolariumNodeUpdate',
            NodeCreatedEvent::class => 'onSolariumNodeUpdate',
            //TagUpdatedEvent::class => 'onSolariumTagUpdate', // Possibly too greedy if lots of nodes tagged
            DocumentFileUploadedEvent::class => 'onSolariumDocumentUpdate',
            DocumentTranslationUpdatedEvent::class => 'onSolariumDocumentUpdate',
            DocumentInFolderEvent::class => 'onSolariumDocumentUpdate',
            DocumentOutFolderEvent::class => 'onSolariumDocumentUpdate',
            DocumentUpdatedEvent::class => 'onSolariumDocumentUpdate',
            DocumentDeletedEvent::class => 'onSolariumDocumentDelete',
            //FolderUpdatedEvent::class => 'onSolariumFolderUpdate', // Possibly too greedy if lots of docs tagged
        ];
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
        // Update Solr Search engine if setup
        if (null !== $this->solr) {
            try {
                $solrSource = $this->getSolariumNodeSource(
                    $event->getNodeSource()
                );
                $solrSource->getDocumentFromIndex();
                $solrSource->updateAndCommit();
            } catch (HttpException $exception) {
                $this->logger->error($exception->getMessage());
            }
        }
    }

    /**
     * Delete solr document for current Node-source.
     *
     * @param NodesSourcesDeletedEvent $event
     */
    public function onSolariumSingleDelete(NodesSourcesDeletedEvent $event)
    {
        // Update Solr Search engine if setup
        if (null !== $this->solr) {
            try {
                $solrSource = $this->getSolariumNodeSource(
                    $event->getNodeSource()
                );
                $solrSource->getDocumentFromIndex();
                $solrSource->removeAndCommit();
            } catch (HttpException $exception) {
                $this->logger->error($exception->getMessage());
            }
        }
    }

    /**
     * Delete solr documents for each Node sources.
     *
     * @param NodeDeletedEvent $event
     */
    public function onSolariumNodeDelete(NodeDeletedEvent $event)
    {
        if (null !== $this->solr) {
            try {
                foreach ($event->getNode()->getNodeSources() as $nodeSource) {
                    $solrSource = $this->getSolariumNodeSource(
                        $nodeSource
                    );
                    $solrSource->getDocumentFromIndex();
                    $solrSource->removeAndCommit();
                }
            } catch (HttpException $exception) {
                $this->logger->error($exception->getMessage());
            }
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
        if (null !== $this->solr) {
            try {
                foreach ($event->getNode()->getNodeSources() as $nodeSource) {
                    $solrSource = $this->getSolariumNodeSource(
                        $nodeSource
                    );
                    $solrSource->getDocumentFromIndex();
                    $solrSource->updateAndCommit();
                }
                $event->stopPropagation();
            } catch (HttpException $exception) {
                $this->logger->error($exception->getMessage());
            }
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
        if (null !== $this->solr && $document instanceof Document) {
            try {
                foreach ($document->getDocumentTranslations() as $documentTranslation) {
                    $solarium = $this->getSolariumDocumentTranslation(
                        $documentTranslation
                    );
                    $solarium->getDocumentFromIndex();
                    $solarium->removeAndCommit();
                }
            } catch (HttpException $exception) {
                $this->logger->error($exception->getMessage());
            }
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
        if (null !== $this->solr && $document instanceof Document) {
            try {
                foreach ($document->getDocumentTranslations() as $documentTranslation) {
                    $solarium = $this->getSolariumDocumentTranslation(
                        $documentTranslation
                    );
                    $solarium->getDocumentFromIndex();
                    $solarium->updateAndCommit();
                }
            } catch (HttpException $exception) {
                $this->logger->error($exception->getMessage());
            }
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
        if (null !== $this->solr) {
            try {
                $update = $this->solr->createUpdate();
                $nodes = $event->getTag()->getNodes();

                foreach ($nodes as $node) {
                    foreach ($node->getNodeSources() as $nodeSource) {
                        $solrSource = $this->getSolariumNodeSource(
                            $nodeSource
                        );
                        $solrSource->getDocumentFromIndex();
                        $solrSource->update($update);
                    }
                }
                $this->solr->update($update);

                // then optimize
                $optimizeUpdate = $this->solr->createUpdate();
                $optimizeUpdate->addOptimize(true, true, 5);
                $this->solr->update($optimizeUpdate);
                // and commit
                $finalCommitUpdate = $this->solr->createUpdate();
                $finalCommitUpdate->addCommit(true, true, false);
                $this->solr->update($finalCommitUpdate);
            } catch (HttpException $exception) {
                $this->logger->error($exception->getMessage());
            }
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
        if (null !== $this->solr) {
            try {
                $update = $this->solr->createUpdate();
                $documents = $event->getFolder()->getDocuments();

                /** @var Document $document */
                foreach ($documents as $document) {
                    foreach ($document->getDocumentTranslations() as $documentTranslation) {
                        $solarium = $this->getSolariumDocumentTranslation(
                            $documentTranslation
                        );
                        $solarium->getDocumentFromIndex();
                        $solarium->update($update);
                    }
                }
                $this->solr->update($update);

                // then optimize
                $optimizeUpdate = $this->solr->createUpdate();
                $optimizeUpdate->addOptimize(true, true, 5);
                $this->solr->update($optimizeUpdate);
                // and commit
                $finalCommitUpdate = $this->solr->createUpdate();
                $finalCommitUpdate->addCommit(true, true, false);
                $this->solr->update($finalCommitUpdate);
            } catch (HttpException $exception) {
                $this->logger->error($exception->getMessage());
            }
        }
    }

    /**
     * @param NodesSources $nodeSource
     *
     * @return SolariumNodeSource
     */
    protected function getSolariumNodeSource(NodesSources $nodeSource): SolariumNodeSource
    {
        return $this->solariumFactory->createWithNodesSources($nodeSource);
    }

    /**
     * @param DocumentTranslation $documentTranslation
     *
     * @return SolariumDocumentTranslation
     */
    protected function getSolariumDocumentTranslation(DocumentTranslation $documentTranslation): SolariumDocumentTranslation
    {
        return $this->solariumFactory->createWithDocumentTranslation($documentTranslation);
    }
}
