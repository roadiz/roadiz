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
    /**
     * @var Folder|null
     */
    protected $folder = null;

    /**
     * @return Folder
     */
    public function getFolder()
    {
        return $this->folder;
    }

    /**
     * @param Folder $folder
     * @return $this
     */
    public function setFolder($folder)
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
        foreach ($this->folder->getChildren() as $folder) {
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

        $this->objectManager->remove($this->folder);

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
        $parent = $this->folder;

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

        $path[] = $this->folder->getFolderName();

        return implode('/', $path);
    }

    /**
     * Clean position for current folder siblings.
     *
     * @param bool $setPositions
     * @return int Return the next position after the **last** folder
     */
    public function cleanPositions($setPositions = true)
    {
        if ($this->folder->getParent() !== null) {
            $parentHandler = new FolderHandler($this->objectManager);
            $parentHandler->setFolder($this->folder->getParent());
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
     * @return int Return the next position after the **last** folder
     */
    public function cleanChildrenPositions($setPositions = true)
    {
        /*
         * Force collection to sort on position
         */
        $sort = Criteria::create();
        $sort->orderBy([
            'position' => Criteria::ASC
        ]);

        $children = $this->folder->getChildren()->matching($sort);
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
     * @return int Return the next position after the **last** folder
     */
    public function cleanRootFoldersPositions($setPositions = true)
    {
        /** @var \RZ\Roadiz\Core\Entities\Folder[] $folders */
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
