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
 * @file DownscaleImageManager.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Utils\Document;

use Doctrine\ORM\EntityManager;
use Intervention\Image\Constraint;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;
use Psr\Log\LoggerInterface;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Utils\Asset\Packages;
use Symfony\Component\Filesystem\Filesystem;

class DownscaleImageManager
{
    protected $maxPixelSize = 0;
    protected $rawImageSuffix = ".raw";
    /**
     * @var ImageManager
     */
    protected $manager;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var EntityManager
     */
    protected $em;
    /**
     * @var Packages
     */
    private $packages;

    /**
     * @param EntityManager $em
     * @param Packages $packages
     * @param LoggerInterface $logger
     * @param string $imageDriver
     * @param integer $maxPixelSize
     * @param string $rawImageSuffix
     */
    public function __construct(
        EntityManager $em,
        Packages $packages,
        LoggerInterface $logger = null,
        $imageDriver = 'gd',
        $maxPixelSize = 0,
        $rawImageSuffix = ".raw"
    ) {
        $this->maxPixelSize = (int) $maxPixelSize;
        $this->rawImageSuffix = $rawImageSuffix;
        $this->em = $em;
        $this->logger = $logger;
        $this->manager = new ImageManager(['driver' => $imageDriver]);
        $this->packages = $packages;
    }

    /**
     * Downscale document if needed, overriding raw document.
     *
     * @param  Document|null $document
     */
    public function processAndOverrideDocument(Document $document = null)
    {
        if (null !== $document && $this->maxPixelSize > 0) {
            $rawDocumentFilePath = $this->packages->getDocumentFilePath($document);

            if (false !== $processImage = $this->getDownscaledImage($this->manager->make($rawDocumentFilePath))) {
                if (false !== $this->createDocumentFromImage($document, $processImage) &&
                    null !== $this->logger) {
                    $this->logger->info('Document has been downscaled.', [
                        'path' => $rawDocumentFilePath
                    ]);
                }
            }
        }
    }

    /**
     * Downscale document if needed, keeping existing raw document.
     *
     * @param  Document|null $document
     */
    public function processDocumentFromExistingRaw(Document $document = null)
    {
        if (null !== $document && $this->maxPixelSize > 0) {
            if (null !== $document->getRawDocument()) {
                $rawDocumentFile = $this->packages->getDocumentFilePath($document->getRawDocument());
            } else {
                $rawDocumentFile = $this->packages->getDocumentFilePath($document);
            }

            if (false !== $processImage = $this->getDownscaledImage($this->manager->make($rawDocumentFile))) {
                if (false !== $this->createDocumentFromImage($document, $processImage, true) &&
                    null !== $this->logger) {
                    $this->logger->info('Document has been downscaled.', ['path' => $rawDocumentFile]);
                }
            }
        }
    }

    /**
     * Get downscaled image if size is higher than limit,
     * returns original image if lower or if image is a GIF.
     *
     * @param  Image  $processImage
     * @return Image
     */
    protected function getDownscaledImage(Image $processImage)
    {
        if ($processImage->mime() != 'image/gif' &&
            ($processImage->width() > $this->maxPixelSize || $processImage->height() > $this->maxPixelSize)) {
            // prevent possible upsizing
            $processImage->resize($this->maxPixelSize, $this->maxPixelSize, function (Constraint $constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

            return $processImage;
        } else {
            return null;
        }
    }

    /**
     * @param  Document $originalDocument
     * @param  Image|null $processImage
     * @param  boolean $keepExistingRaw
     * @return Document|bool Return new Document or FALSE
     */
    protected function createDocumentFromImage(Document $originalDocument, Image $processImage = null, $keepExistingRaw = false)
    {
        $fs = new Filesystem();

        if (false === $keepExistingRaw && null !== $formerRawDoc = $originalDocument->getRawDocument()) {
            /*
             * When document already exists with a raw doc reference.
             * We have to delete former raw document before creating a new one.
             * Keeping the same document to preserve existing relationships!!
             */
            $originalDocument->setRawDocument(null);
            /*
             * Make sure to disconnect raw document before removing it
             * not to trigger Cascade deleting.
             */
            $this->em->flush();
            $this->em->remove($formerRawDoc);
            $this->em->flush();
        }

        if (null === $originalDocument->getRawDocument() || $keepExistingRaw === false) {
            /*
             * We clone it to host raw document.
             * Keeping the same document to preserve existing relationships!!
             *
             * Get every data from raw document.
             */
            if (null !== $processImage) {
                $rawDocument = clone $originalDocument;
                $rawDocumentName = preg_replace(
                    '#\.(jpe?g|gif|tiff?|png|psd)$#',
                    $this->rawImageSuffix . '.$1',
                    $originalDocument->getFilename()
                );
                $rawDocument->setFilename($rawDocumentName);

                $originalDocumentPath = $this->packages->getDocumentFilePath($originalDocument);
                $rawDocumentPath = $this->packages->getDocumentFilePath($rawDocument);

                if ($fs->exists($originalDocumentPath) &&
                    !$fs->exists($rawDocumentPath)) {
                    /*
                     * Original document path becomes raw document path. Rename it.
                     */
                    $fs->rename($originalDocumentPath, $rawDocumentPath);
                    /*
                     * Then save downscaled image as original document path.
                     */
                    $processImage->save($originalDocumentPath, 100);

                    $originalDocument->setRawDocument($rawDocument);
                    $rawDocument->setRaw(true);

                    $this->em->persist($rawDocument);
                    $this->em->flush();

                    return $originalDocument;
                } else {
                    return false;
                }
            } else {
                return $originalDocument;
            }
        } elseif (null !== $processImage) {
            $originalDocumentPath = $this->packages->getDocumentFilePath($originalDocument);

            /*
             * Remove existing downscaled document.
             */
            $fs->remove($originalDocumentPath);
            /*
             * Then save downscaled image as original document path.
             */
            $processImage->save($originalDocumentPath, 100);

            $this->em->flush();

            return $originalDocument;
        } else {
            /*
             * If raw document size is inside new maxSize cap
             * we delete it and use it as new active document file.
             */
            $rawDocument = $originalDocument->getRawDocument();

            $originalDocumentPath = $this->packages->getDocumentFilePath($originalDocument);
            $rawDocumentPath = $this->packages->getDocumentFilePath($rawDocument);

            /*
             * Remove existing downscaled document.
             */
            $fs->remove($originalDocumentPath);
            $fs->copy($rawDocumentPath, $originalDocumentPath, true);

            /*
             * Remove Raw document
             */
            $originalDocument->setRawDocument(null);
            /*
             * Make sure to disconnect raw document before removing it
             * not to trigger Cascade deleting.
             */
            $this->em->flush();
            $this->em->remove($rawDocument);
            $this->em->flush();

            return $originalDocument;
        }
    }
}
