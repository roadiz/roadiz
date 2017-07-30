<?php
/**
 * Copyright © 2014, Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
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
 * @file FolderHandler.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Handlers;

use RZ\Roadiz\Core\Entities\Folder;

/**
 * Handle operations with folders entities.
 */
class FolderHandler extends AbstractHandler
{
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
     * Create a new folder handler with folder to handle.
     *
     * @param Folder|null $folder
     */
    public function __construct(Folder $folder = null)
    {
        parent::__construct();
        $this->folder = $folder;
    }

    /**
     * Remove only current folder children.
     *
     * @return $this
     */
    private function removeChildren()
    {
        foreach ($this->folder->getChildren() as $folder) {
            $folder->getHandler()->removeWithChildrenAndAssociations();
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

        $this->entityManager->remove($this->folder);

        /*
         * Final flush
         */
        $this->entityManager->flush();

        return $this;
    }

    /**
     * Return every folder’s parents.
     *
     * @return \RZ\Roadiz\Core\Entities\Folder[]
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
     * @return int Return the next position after the **last** folder
     */
    public function cleanPositions()
    {
        if ($this->folder->getParent() !== null) {
            $parentHandler = new FolderHandler();
            $parentHandler->setFolder($this->folder->getParent());
            return $parentHandler->cleanChildrenPositions();
        } else {
            return $this->cleanRootFoldersPositions();
        }
    }

    /**
     * Reset current folder children positions.
     *
     * @return int Return the next position after the **last** folder
     */
    public function cleanChildrenPositions()
    {
        $children = $this->folder->getChildren();
        $i = 1;
        foreach ($children as $child) {
            $child->setPosition($i);
            $i++;
        }

        $this->entityManager->flush();

        return $i;
    }

    /**
     * Reset every root folders positions.
     *
     * @return int Return the next position after the **last** folder
     */
    public function cleanRootFoldersPositions()
    {
        /** @var \RZ\Roadiz\Core\Entities\Folder[] $folders */
        $folders = $this->entityManager
            ->getRepository('RZ\Roadiz\Core\Entities\Folder')
            ->findBy(['parent' => null], ['position'=>'ASC']);

        $i = 1;
        foreach ($folders as $child) {
            $child->setPosition($i);
            $i++;
        }

        $this->entityManager->flush();

        return $i;
    }
}
