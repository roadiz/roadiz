<?php
declare(strict_types=1);

namespace Themes\Rozier\Explorer;

use RZ\Roadiz\Core\Entities\Folder;
use RZ\Roadiz\Explorer\AbstractDoctrineExplorerProvider;
use RZ\Roadiz\Explorer\ExplorerItemInterface;

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
    public function supports($item): bool
    {
        if ($item instanceof Folder) {
            return true;
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function toExplorerItem($item): ?ExplorerItemInterface
    {
        if ($item instanceof Folder) {
            return new FolderExplorerItem($item);
        }
        throw new \InvalidArgumentException('Explorer item must be instance of ' . Folder::class);
    }
}
