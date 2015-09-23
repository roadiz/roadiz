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
 * @file RawDocumentsSubscriber.php
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Events;

use Doctrine\ORM\EntityManager;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;
use Psr\Log\LoggerInterface;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Events\DocumentEvents;
use RZ\Roadiz\Core\Events\FilterDocumentEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Create a raw image and downscale it to a new image file for a better web usage.
 */
class RawDocumentsSubscriber implements EventSubscriberInterface
{

    protected $maxPixelSize = 0;
    protected $rawImageSuffix = ".raw";
    protected $manager;
    protected $logger;
    protected $em;

    /**
     * @param EntityManager $imageDriver
     * @param string  $imageDriver
     * @param integer $maxPixelSize
     * @param string  $rawImageSuffix
     */
    public function __construct(
        EntityManager $em,
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
    }

    public static function getSubscribedEvents()
    {
        return [
            DocumentEvents::DOCUMENT_IMAGE_UPLOADED => 'onDocumentImageUploaded',
        ];
    }

    public function onDocumentImageUploaded(FilterDocumentEvent $event)
    {
        $document = $event->getDocument();
        if (null !== $document && $this->maxPixelSize > 0) {
            $rawDocumentFile = $document->getAbsolutePath();

            if (false !== $processImage = $this->getDownscaledImage($this->manager->make($rawDocumentFile))) {
                if (false !== $this->createDocumentFromImage($processImage, $document) &&
                    null !== $this->logger) {
                    $this->logger->info('Document ' . $document->getAbsolutePath() . ' has been downscaled.', ['path' => $document->getAbsolutePath()]);
                }
            }
        }
    }

    protected function getDownscaledImage(Image $processImage)
    {
        if ($processImage->width() > $this->maxPixelSize ||
            $processImage->height() > $this->maxPixelSize) {
            // prevent possible upsizing
            $processImage->resize($this->maxPixelSize, $this->maxPixelSize, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

            return $processImage;

        } else {
            return false;
        }
    }

    /**
     * @param  Image    $processImage
     * @param  Document $rawDocument
     * @return Document
     */
    protected function createDocumentFromImage(Image $processImage, Document $originalDocument)
    {
        $fs = new Filesystem();

        if (null !== $formerRawDoc = $originalDocument->getRawDocument()) {
            /*
             * When document already exists with a raw doc reference.
             * We have to delete former raw document before creating a new one.
             * Keeping the same document to preserve existing relationships!!
             */
            $originalDocument->setRawDocument(null);
            $this->em->remove($formerRawDoc);
            $this->em->flush();
        }
        /*
         * We clone it to host raw document.
         * Keeping the same document to preserve existing relationships!!
         *
         * Get every data from raw document.
         */
        $rawDocument = clone $originalDocument;
        $rawDocumentName = preg_replace('#\.(jpe?g|gif|tiff?|png|psd)$#', $this->rawImageSuffix . '.$1', $originalDocument->getFilename());
        $rawDocument->setFilename($rawDocumentName);

        if ($fs->exists($originalDocument->getAbsolutePath()) &&
            !$fs->exists($rawDocument->getAbsolutePath())) {
            /*
             * Original document path becomes raw document path. Rename it.
             */
            $fs->rename($originalDocument->getAbsolutePath(), $rawDocument->getAbsolutePath());
            /*
             * Then save downscaled image as original document path.
             */
            $processImage->save($originalDocument->getAbsolutePath(), 100);

            $originalDocument->setRawDocument($rawDocument);
            $rawDocument->setRaw(true);

            $this->em->persist($rawDocument);
            $this->em->flush();

            return $originalDocument;

        } else {
            return false;
        }
    }
}
