<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Events;

use RZ\Roadiz\Core\Events\Node\NodeCreatedEvent;
use RZ\Roadiz\Core\Events\Node\NodeDeletedEvent;
use RZ\Roadiz\Core\Events\Node\NodeDuplicatedEvent;
use RZ\Roadiz\Core\Events\Node\NodeStatusChangedEvent;
use RZ\Roadiz\Core\Events\Node\NodeTaggedEvent;
use RZ\Roadiz\Core\Events\Node\NodeUndeletedEvent;
use RZ\Roadiz\Core\Events\Node\NodeUpdatedEvent;
use RZ\Roadiz\Core\Events\Node\NodeVisibilityChangedEvent;

/**
 * @deprecated
 */
final class NodeEvents
{
    /**
     * Event node.created is triggered each time a node
     * is created.
     *
     * Event listener will be given a:
     * RZ\Roadiz\Core\Events\FilterNodeEvent instance
     *
     * @var string
     * @deprecated
     */
    const NODE_CREATED = NodeCreatedEvent::class;

    /**
     * Event node.updated is triggered each time a node
     * is updated.
     *
     * Event listener will be given a:
     * RZ\Roadiz\Core\Events\FilterNodeEvent instance
     *
     * @var string
     * @deprecated
     */
    const NODE_UPDATED = NodeUpdatedEvent::class;

    /**
     * Event node.deleted is triggered each time a node
     * is deleted.
     *
     * Event listener will be given a:
     * RZ\Roadiz\Core\Events\FilterNodeEvent instance
     *
     * @var string
     * @deprecated
     */
    const NODE_DELETED = NodeDeletedEvent::class;

    /**
     * Event node.duplicated is triggered each time a node
     * is duplicated.
     *
     * Event listener will be given a:
     * RZ\Roadiz\Core\Events\FilterNodeEvent instance
     *
     * @var string
     * @deprecated
     */
    const NODE_DUPLICATED = NodeDuplicatedEvent::class;

    /**
     * Event node.undeleted is triggered each time a node
     * is undeleted.
     *
     * Event listener will be given a:
     * RZ\Roadiz\Core\Events\FilterNodeEvent instance
     *
     * @var string
     * @deprecated
     */
    const NODE_UNDELETED = NodeUndeletedEvent::class;

    /**
     * Event node.tagged is triggered each time a node
     * is linked or unlinked with a Tag.
     *
     * Event listener will be given a:
     * RZ\Roadiz\Core\Events\FilterNodeEvent instance
     *
     * @var string
     * @deprecated
     */
    const NODE_TAGGED = NodeTaggedEvent::class;

    /**
     * Event node.visibilityChanged is triggered each time a node
     * becomes visible or unvisible.
     *
     * Event listener will be given a:
     * RZ\Roadiz\Core\Events\FilterNodeEvent instance
     *
     * @var string
     * @deprecated
     */
    const NODE_VISIBILITY_CHANGED = NodeVisibilityChangedEvent::class;

    /**
     * Event node.statusChanged is triggered each time a node
     * status changes.
     *
     * Event listener will be given a:
     * RZ\Roadiz\Core\Events\FilterNodeEvent instance
     *
     * @var string
     * @deprecated
     */
    const NODE_STATUS_CHANGED = NodeStatusChangedEvent::class;
}
