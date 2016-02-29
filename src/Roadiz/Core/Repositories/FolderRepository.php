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
 * @file FolderRepository.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Repositories;

use RZ\Roadiz\Core\Entities\Folder;

/**
 * {@inheritdoc}
 */
class FolderRepository extends EntityRepository
{

    /**
     * Find a folder according to the given path or create it.
     *
     * @param string $folderPath
     *
     * @return \RZ\Roadiz\Core\Entities\Folder
     */
    public function findOrCreateByPath($folderPath)
    {
        $folderPath = trim($folderPath);

        $folders = explode('/', $folderPath);
        $folders = array_filter($folders);

        $folderName = $folders[count($folders) - 1];
        $parentFolder = null;

        if (count($folders) > 1) {
            $parentName = $folders[count($folders) - 2];

            $parentFolder = $this->_em
                        ->getRepository('RZ\Roadiz\Core\Entities\Folder')
                        ->findOneByName($parentName);
        }

        $folder = $this->_em
                    ->getRepository('RZ\Roadiz\Core\Entities\Folder')
                    ->findOneByName($folderName);


        if (null === $folder) {
            /*
             * Creation of a new folder
             * before linking it to the node
             */
            $folder = new Folder();
            $folder->setName($folderName);

            if (null !== $parentFolder) {
                $folder->setParent($parentFolder);
            }

            $this->_em->persist($folder);
            $this->_em->flush();
        }

        return $folder;
    }

    /**
     * Find a folder according to the given path.
     *
     * @param string $folderPath
     *
     * @return \RZ\Roadiz\Core\Entities\Folder|null
     */
    public function findByPath($folderPath)
    {
        $folderPath = trim($folderPath);

        $folders = explode('/', $folderPath);
        $folders = array_filter($folders);

        $folderName = $folders[count($folders) - 1];

        return $this->_em
                    ->getRepository('RZ\Roadiz\Core\Entities\Folder')
                    ->findOneByName($folderName);

    }
}
