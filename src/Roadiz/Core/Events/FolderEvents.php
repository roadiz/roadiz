<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Events;

use RZ\Roadiz\Core\Events\Folder\FolderCreatedEvent;
use RZ\Roadiz\Core\Events\Folder\FolderDeletedEvent;
use RZ\Roadiz\Core\Events\Folder\FolderUpdatedEvent;

/**
 * @package RZ\Roadiz\Core\Events
 * @deprecated
 */
final class FolderEvents
{
    /**
     * Event folder.created is triggered each time a node-source
     * is created.
     *
     * Evetn listener will be given a:
     * RZ\Roadiz\Core\Events\FilterFolderEvent instance
     *
     * @var string
     * @deprecated
     */
    const FOLDER_CREATED = FolderCreatedEvent::class;

    /**
     * Event folder.updated is triggered each time a node-source
     * is updated.
     *
     * Evetn listener will be given a:
     * RZ\Roadiz\Core\Events\FilterFolderEvent instance
     *
     * @var string
     * @deprecated
     */
    const FOLDER_UPDATED = FolderUpdatedEvent::class;

    /**
     * Event folder.deleted is triggered each time a node-source
     * is deleted.
     *
     * Evetn listener will be given a:
     * RZ\Roadiz\Core\Events\FilterFolderEvent instance
     *
     * @var string
     * @deprecated
     */
    const FOLDER_DELETED = FolderDeletedEvent::class;
}
