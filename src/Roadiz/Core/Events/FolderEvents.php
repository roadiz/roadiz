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
 * @file FolderEvents.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */
namespace RZ\Roadiz\Core\Events;

use RZ\Roadiz\Core\Events\Folder\FolderCreatedEvent;
use RZ\Roadiz\Core\Events\Folder\FolderDeletedEvent;
use RZ\Roadiz\Core\Events\Folder\FolderUpdatedEvent;

/**
 * Class FolderEvents
 *
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
