<?php
declare(strict_types=1);

namespace Themes\Rozier\Explorer;

use RZ\Roadiz\Core\Entities\Folder;

final class FolderExplorerItem extends AbstractExplorerItem
{
    private Folder $folder;

    /**
     * @param Folder $folder
     */
    public function __construct(Folder $folder)
    {
        $this->folder = $folder;
    }

    /**
     * @inheritDoc
     */
    public function getId()
    {
        return $this->folder->getId();
    }

    /**
     * @inheritDoc
     */
    public function getAlternativeDisplayable()
    {
        /** @var Folder|null $parent */
        $parent = $this->folder->getParent();
        if (null !== $parent) {
            return $parent->getTranslatedFolders()->first()->getName();
        }
        return '';
    }

    /**
     * @inheritDoc
     */
    public function getDisplayable()
    {
        return $this->folder->getTranslatedFolders()->first()->getName();
    }

    /**
     * @inheritDoc
     */
    public function getOriginal()
    {
        return $this->folder;
    }
}
