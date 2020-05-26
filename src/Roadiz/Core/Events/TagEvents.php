<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Events;

use RZ\Roadiz\Core\Events\Tag\TagCreatedEvent;
use RZ\Roadiz\Core\Events\Tag\TagDeletedEvent;
use RZ\Roadiz\Core\Events\Tag\TagUpdatedEvent;

/**
 * @deprecated
 */
final class TagEvents
{
    /**
     * Event tag.created is triggered each time a node-source
     * is created.
     *
     * Evetn listener will be given a:
     * RZ\Roadiz\Core\Events\FilterTagEvent instance
     *
     * @var string
     * @deprecated
     */
    const TAG_CREATED = TagCreatedEvent::class;

    /**
     * Event tag.updated is triggered each time a node-source
     * is updated.
     *
     * Evetn listener will be given a:
     * RZ\Roadiz\Core\Events\FilterTagEvent instance
     *
     * @var string
     * @deprecated
     */
    const TAG_UPDATED = TagUpdatedEvent::class;

    /**
     * Event tag.deleted is triggered each time a node-source
     * is deleted.
     *
     * Evetn listener will be given a:
     * RZ\Roadiz\Core\Events\FilterTagEvent instance
     *
     * @var string
     * @deprecated
     */
    const TAG_DELETED = TagDeletedEvent::class;
}
