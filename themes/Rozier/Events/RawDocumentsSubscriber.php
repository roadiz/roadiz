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
use Psr\Log\LoggerInterface;
use RZ\Roadiz\Core\Events\DocumentEvents;
use RZ\Roadiz\Core\Events\FilterDocumentEvent;
use RZ\Roadiz\Utils\Asset\Packages;
use RZ\Roadiz\Utils\Document\DownscaleImageManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Create a raw image and downscale it to a new image file for a better web usage.
 */
class RawDocumentsSubscriber implements EventSubscriberInterface
{
    /** @var DownscaleImageManager */
    protected $manager;

    /**
     * @param EntityManager $em
     * @param Packages $packages
     * @param LoggerInterface $logger
     * @param EntityManager|string $imageDriver
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
        $this->manager = new DownscaleImageManager($em, $packages, $logger, $imageDriver, $maxPixelSize, $rawImageSuffix);
    }

    public static function getSubscribedEvents()
    {
        return [
            // Keeps Raw document process before any other document subscribers to perform operations
            // on a lower image
            DocumentEvents::DOCUMENT_IMAGE_UPLOADED => ['onImageUploaded', 100],
        ];
    }

    public function onImageUploaded(FilterDocumentEvent $event)
    {
        if (null !== $event->getDocument() && $event->getDocument()->isProcessable()) {
            $this->manager->processAndOverrideDocument($event->getDocument());
        }
    }
}
