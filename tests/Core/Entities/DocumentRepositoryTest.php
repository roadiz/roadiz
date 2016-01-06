<?php
/**
 * Copyright © 2015, Ambroise Maupate and Julien Blanchet
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
 * @file DocumentRepositoryTest.php
 * @author Ambroise Maupate
 */
use Doctrine\Common\Collections\ArrayCollection;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Entities\DocumentTranslation;
use RZ\Roadiz\Core\Entities\Folder;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Tests\KernelDependentCase;

class DocumentRepositoryTest extends KernelDependentCase
{
    private static $documentCollection;
    private static $folderCollection;

    /**
     * @dataProvider testDocumentFoldersProvider
     */
    public function testDocumentFolders($documentFilename, $expectedFolderCount)
    {
        $document = Kernel::getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\Document')
            ->findOneByFilename($documentFilename);

        $this->assertEquals($expectedFolderCount, count($document->getFolders()));
    }

    public function testDocumentFoldersProvider()
    {
        return [
            ["unittest_document1", 2],
            ["unittest_document2", 2],
            ["unittest_document3", 3],
        ];
    }

    /**
     * @dataProvider getByFolderInclusiveProvider
     */
    public function testGetByFolderInclusive($foldersNames, $expectedDocumentCount)
    {
        $folders = Kernel::getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\Folder')
            ->findByName($foldersNames);

        $documentCount = Kernel::getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\Document')
            ->countBy([
                'folders' => $folders,
            ]);

        $this->assertEquals($expectedDocumentCount, $documentCount);
    }

    public function getByFolderInclusiveProvider()
    {
        return [
            [['unittest-folder-1'], 3],
            [['unittest-folder-2'], 1],
            [['unittest-folder-3'], 1],
            [['unittest-folder-1', 'unittest-folder-2'], 3],
            [['unittest-folder-1', 'unittest-folder-3'], 3],
            [['unittest-folder-2', 'unittest-folder-3'], 2],
            [['unittest-folder-1', 'unittest-folder-4'], 3],
        ];
    }

    /**
     * @dataProvider getByFolderExclusiveProvider
     */
    public function testGetByFolderExclusive($foldersNames, $expectedDocumentCount)
    {
        $folders = Kernel::getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\Folder')
            ->findByName($foldersNames);

        $documentCount = Kernel::getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\Document')
            ->countBy([
                'folders' => $folders,
                'folderExclusive' => true,
            ]);

        $this->assertEquals($expectedDocumentCount, $documentCount);
    }

    public function getByFolderExclusiveProvider()
    {
        return [
            [['unittest-folder-1'], 3],
            [['unittest-folder-2'], 1],
            [['unittest-folder-3'], 1],
            [['unittest-folder-1', 'unittest-folder-2'], 1],
            [['unittest-folder-1', 'unittest-folder-3'], 1],
            [['unittest-folder-2', 'unittest-folder-3'], 0],
            [['unittest-folder-1', 'unittest-folder-4'], 2],
        ];
    }

    /*
     * ============================================================================
     * fixtures
     * ============================================================================
     */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        static::$documentCollection = new ArrayCollection();
        static::$folderCollection = new ArrayCollection();

        $folders = [
            'unittest-folder-1',
            'unittest-folder-2',
            'unittest-folder-3',
            'unittest-folder-4',
        ];
        $documents = [
            ["unittest_document1", ['unittest-folder-1', 'unittest-folder-4']],
            ["unittest_document2", ['unittest-folder-1', 'unittest-folder-2']],
            ["unittest_document3", ['unittest-folder-1', 'unittest-folder-3', 'unittest-folder-4']],
        ];

        $translation = Kernel::getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\Translation')
            ->findDefault();

        /*
         * Adding Folders
         */
        foreach ($folders as $value) {
            $folder = Kernel::getService('em')
                ->getRepository('RZ\Roadiz\Core\Entities\Folder')
                ->findOneByName($value);

            if (null === $folder) {
                $folder = new Folder();
                $folder->setName($value);
                Kernel::getService('em')->persist($folder);

                static::$folderCollection->add($folder);
            }
        }
        Kernel::getService('em')->flush();

        /*
         * Adding documents
         */
        foreach ($documents as $value) {
            $document = Kernel::getService('em')
                ->getRepository('RZ\Roadiz\Core\Entities\Document')
                ->findOneByFilename($value[0]);

            if (null === $document) {
                $document = new Document();
                $document->setFilename($value[0]);
                Kernel::getService('em')->persist($document);

                $dt = new DocumentTranslation();
                $dt->setDocument($document);
                $dt->setTranslation($translation);
                Kernel::getService('em')->persist($dt);

                static::$documentCollection->add($document);
            }
            /*
             * Adding folders
             */
            foreach ($value[1] as $folderName) {
                $folder = Kernel::getService('em')
                    ->getRepository('RZ\Roadiz\Core\Entities\Folder')
                    ->findOneByName($folderName);
                if (null !== $folder) {
                    $document->addFolder($folder);
                    $folder->addDocument($document);
                } else {
                    throw new \RuntimeException("Folder does not exist: " . $folderName, 1);
                }
            }
        }
        Kernel::getService('em')->flush();
    }

    /**
     * Remove test entities.
     */
    public static function tearDownAfterClass()
    {
        foreach (static::$documentCollection as $document) {
            Kernel::getService('em')->remove($document);
        }
        foreach (static::$folderCollection as $folder) {
            Kernel::getService('em')->remove($folder);
        }

        Kernel::getService('em')->flush();

        parent::tearDownAfterClass();
    }
}
