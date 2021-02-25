<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Handlers;

use Doctrine\Common\Collections\Criteria;
use RZ\Roadiz\Core\AbstractEntities\LeafInterface;
use RZ\Roadiz\Core\Entities\Folder;

/**
 * Handle operations with folders entities.
 */
class FolderHandler extends AbstractHandler
{
    protected ?Folder $folder = null;

    public function getFolder(): Folder
    {
        if (null === $this->folder) {
            throw new \BadMethodCallException('Folder is null');
        }
        return $this->folder;
    }

    /**
     * @param Folder $folder
     * @return $this
     */
    public function setFolder(Folder $folder)
    {
        $this->folder = $folder;
        return $this;
    }

    /**
     * Remove only current folder children.
     *
     * @return $this
     */
    private function removeChildren()
    {
        /** @var Folder $folder */
        foreach ($this->getFolder()->getChildren() as $folder) {
            $handler = new FolderHandler($this->objectManager);
            $handler->setFolder($folder);
            $handler->removeWithChildrenAndAssociations();
        }

        return $this;
    }

    /**
     * Remove current folder with its children recursively and
     * its associations.
     *
     * @return $this
     */
    public function removeWithChildrenAndAssociations()
    {
        $this->removeChildren();
        $this->objectManager->remove($this->getFolder());

        /*
         * Final flush
         */
        $this->objectManager->flush();
        return $this;
    }

    /**
     * Return every folder’s parents.
     *
     * @deprecated Use directly Folder::getParents method.
     * @return array<LeafInterface|Folder>
     */
    public function getParents()
    {
        $parentsArray = [];
        $parent = $this->getFolder();

        do {
            $parent = $parent->getParent();
            if ($parent !== null) {
                $parentsArray[] = $parent;
            } else {
                break;
            }
        } while ($parent !== null);

        return array_reverse($parentsArray);
    }

    /**
     * Get folder full path using folder names.
     *
     * @deprecated Use directly Folder::getFullPath method.
     * @return string
     */
    public function getFullPath()
    {
        $parents = $this->getParents();
        $path = [];

        foreach ($parents as $parent) {
            $path[] = $parent->getFolderName();
        }

        $path[] = $this->getFolder()->getFolderName();

        return implode('/', $path);
    }

    /**
     * Clean position for current folder siblings.
     *
     * @param bool $setPositions
     * @return float Return the next position after the **last** folder
     */
    public function cleanPositions(bool $setPositions = true): float
    {
        if ($this->getFolder()->getParent() !== null) {
            $parentHandler = new FolderHandler($this->objectManager);
            $parentHandler->setFolder($this->getFolder()->getParent());
            return $parentHandler->cleanChildrenPositions($setPositions);
        } else {
            return $this->cleanRootFoldersPositions($setPositions);
        }
    }

    /**
     * Reset current folder children positions.
     *
     * Warning, this method does not flush.
     *
     * @param bool $setPositions
     * @return float Return the next position after the **last** folder
     */
    public function cleanChildrenPositions(bool $setPositions = true): float
    {
        /*
         * Force collection to sort on position
         */
        $sort = Criteria::create();
        $sort->orderBy([
            'position' => Criteria::ASC
        ]);

        $children = $this->getFolder()->getChildren()->matching($sort);
        $i = 1;
        /** @var Folder $child */
        foreach ($children as $child) {
            if ($setPositions) {
                $child->setPosition($i);
            }
            $i++;
        }

        return $i;
    }

    /**
     * Reset every root folders positions.
     *
     * Warning, this method does not flush.
     *
     * @param bool $setPositions
     * @return float Return the next position after the **last** folder
     */
    public function cleanRootFoldersPositions(bool $setPositions = true): float
    {
        /** @var Folder[] $folders */
        $folders = $this->objectManager
            ->getRepository(Folder::class)
            ->findBy(['parent' => null], ['position'=>'ASC']);

        $i = 1;
        foreach ($folders as $child) {
            if ($setPositions) {
                $child->setPosition($i);
            }
            $i++;
        }

        return $i;
    }
}
