<?php
/**
 * Copyright © 2015, Ambroise Maupate
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
 * @file DownscaleImageManagerTest.php
 * @author Ambroise Maupate
 */

use Doctrine\Common\Collections\ArrayCollection;
use Intervention\Image\ImageManager;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Tests\SchemaDependentCase;
use RZ\Roadiz\Utils\Asset\Packages;
use RZ\Roadiz\Utils\Document\DownscaleImageManager;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;

/**
 * Class DownscaleImageManagerTest
 */
class DownscaleImageManagerTest extends SchemaDependentCase
{
    protected static $files;
    protected static $documentCollection;
    protected static $imageManager;

    public function testConstructor()
    {
        $manager = new DownscaleImageManager(
            $this->get('em'),
            $this->get('assetPackages'),
            $this->get('logger'),
            'gd',
            1920
        );

        $this->assertNotNull($manager);
    }

    public function testProcessAndOverrideDocument()
    {
        $originalHashes = [];

        /** @var Packages $packages */
        $packages =  $this->get('assetPackages');
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('em');

        $manager = new DownscaleImageManager(
            $entityManager,
            $packages,
            $this->get('logger'),
            'gd',
            100
        );

        /**
         * @var int $key
         * @var Document $document
         */
        foreach (static::$documentCollection as $key => $document) {
            $entityManager->refresh($document);
            $originalHashes[$key] = hash_file('md5', $packages->getDocumentFilePath($document));

            $manager->processAndOverrideDocument($document);
            $afterHash = hash_file('md5', $packages->getDocumentFilePath($document));

            if ($document->getMimeType() === 'image/gif') {
                /*
                 * GIF must be untouched
                 */
                $this->assertEquals($originalHashes[$key], $afterHash);
                $this->assertNull($document->getRawDocument());
            } else {
                /*
                 * Other must be downscaled
                 * a raw image should be saved.
                 */
                $this->assertNotEquals($originalHashes[$key], $afterHash, sprintf('%s document file should have been downscaled', $document->getFilename()));
                $this->assertNotNull($document->getRawDocument());

                /*
                 * Raw document must be equal to original file
                 */
                $rawHash = hash_file('md5', $packages->getDocumentFilePath($document->getRawDocument()));
                $this->assertEquals($originalHashes[$key], $rawHash);
            }
        }

        /*
         * Removing the size cap.
         * not more raw and no more difference
         */
        $manager = new DownscaleImageManager(
            $entityManager,
            $packages,
            $this->get('logger'),
            'gd',
            100000
        );

        foreach (static::$documentCollection as $key => $document) {
            $manager->processDocumentFromExistingRaw($document);
            $afterHash = hash_file('md5', $packages->getDocumentFilePath($document));

            $this->assertEquals($originalHashes[$key], $afterHash, 'New document file should be the same the original one');
            $this->assertFalse($document->isRaw());
            $rawDocument = $document->getRawDocument();
            $this->assertNull($rawDocument, sprintf('Raw "%s" version is still present on the document. It should be NULL.', $document->getFilename()));
        }
    }

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        static::$documentCollection = new ArrayCollection();
        $fs = new Filesystem();

        static::$imageManager = new ImageManager();
        static::$files = [
            ROADIZ_ROOT . '/tests/Fixtures/Documents/animation.gif',
            ROADIZ_ROOT . '/tests/Fixtures/Documents/lion.jpg',
            ROADIZ_ROOT . '/tests/Fixtures/Documents/dices.png',
        ];

        foreach (static::$files as $file) {
            $image = new File($file);
            $document = new Document();
            $document->setFolder('phpunit_'.uniqid());
            $document->setFilename($image->getBasename());
            $document->setMimeType($image->getMimeType());

            $fs->copy($file, static::$kernel->getPublicFilesPath() . '/' . $document->getFolder() . '/' . $document->getFilename());

            static::$kernel->get('em')->persist($document);

            static::$documentCollection->add($document);
        }

        static::$kernel->get('em')->flush();
    }
}
