<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Events;

use RZ\Roadiz\Core\Entities\Folder;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class FilterFolderEvent
 * @package RZ\Roadiz\Core\Events
 * @deprecated
 */
abstract class FilterFolderEvent extends Event
{
    /**
     * @var Folder
     */
    protected $folder;

    /**
     * FilterFolderEvent constructor.
     * @param Folder $folder
     */
    public function __construct(Folder $folder)
    {
        $this->folder = $folder;
    }

    /**
     * @return Folder
     */
    public function getFolder(): Folder
    {
        return $this->folder;
    }
}
