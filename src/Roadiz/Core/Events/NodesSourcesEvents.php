<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Events;

use RZ\Roadiz\Core\Events\NodesSources\NodesSourcesCreatedEvent;
use RZ\Roadiz\Core\Events\NodesSources\NodesSourcesDeletedEvent;
use RZ\Roadiz\Core\Events\NodesSources\NodesSourcesIndexingEvent;
use RZ\Roadiz\Core\Events\NodesSources\NodesSourcesPathGeneratingEvent;
use RZ\Roadiz\Core\Events\NodesSources\NodesSourcesPreUpdatedEvent;
use RZ\Roadiz\Core\Events\NodesSources\NodesSourcesUpdatedEvent;

/**
 * @deprecated
 */
final class NodesSourcesEvents
{
    /**
     * Event nodeSource.created is triggered each time a node-source
     * is created.
     *
     * Event listener will be given a:
     * RZ\Roadiz\Core\Events\FilterNodesSourcesEvent instance
     *
     * @var string
     * @deprecated
     */
    const NODE_SOURCE_CREATED = NodesSourcesCreatedEvent::class;

    /**
     * Event nodeSource.updated is triggered each time a node-source
     * is updated.
     * This event is dispatched BEFORE entity manager FLUSHED.
     *
     * Event listener will be given a:
     * RZ\Roadiz\Core\Events\FilterNodesSourcesEvent instance
     *
     * @var string
     * @deprecated
     */
    const NODE_SOURCE_PRE_UPDATE = NodesSourcesPreUpdatedEvent::class;

    /**
     * Event nodeSource.updated is triggered each time a node-source
     * is updated.
     * This event is dispatched AFTER entity manager FLUSHED.
     *
     * Event listener will be given a:
     * RZ\Roadiz\Core\Events\FilterNodesSourcesEvent instance
     *
     * @var string
     * @deprecated
     */
    const NODE_SOURCE_UPDATED = NodesSourcesUpdatedEvent::class;

    /**
     * Event nodeSource.deleted is triggered each time a node-source
     * is deleted.
     *
     * This event is dispatched BEFORE entity manager FLUSHED.
     *
     * Event listener will be given a:
     * RZ\Roadiz\Core\Events\FilterNodesSourcesEvent instance
     *
     * @var string
     * @deprecated
     */
    const NODE_SOURCE_DELETED = NodesSourcesDeletedEvent::class;

    /**
     * Event nodeSource.indexing is triggered each time a node-source
     * is being indexed in a Solr server.
     *
     * Event listener will be given a:
     *
     * @var string
     * @deprecated
     */
    const NODE_SOURCE_INDEXING = NodesSourcesIndexingEvent::class;

    /**
     * Event triggered when a node-source path is being generating by the NodeRouter.
     * This event allows generating different paths according your node types.
     *
     * Event listener will be given a:
     * \RZ\Roadiz\Core\Events\FilterNodeSourcePathEvent instance
     *
     * @var string
     * @deprecated
     */
    const NODE_SOURCE_PATH_GENERATING = NodesSourcesPathGeneratingEvent::class;
}
