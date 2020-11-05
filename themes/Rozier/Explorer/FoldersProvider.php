<?php
declare(strict_types=1);

namespace Themes\Rozier\Explorer;

use RZ\Roadiz\Core\Entities\Folder;

final class FoldersProvider extends AbstractDoctrineExplorerProvider
{
    protected function getProvidedClassname(): string
    {
        return Folder::class;
    }

    protected function getDefaultCriteria(): array
    {
        return [];
    }

    protected function getDefaultOrdering(): array
    {
        return ['folderName' =>'ASC'];
    }

    /**
     * @inheritDoc
     */
    public function supports($item)
    {
        if ($item instanceof Folder) {
            return true;
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function toExplorerItem($item)
    {
        if ($item instanceof Folder) {
            return new FolderExplorerItem($item);
        }
        throw new \InvalidArgumentException('Explorer item must be instance of ' . Folder::class);
    }
}
