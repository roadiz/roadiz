<?php
/**
 * Copyright (c) 2017. Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
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
 * @file ExifDocumentSubscriber.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace Themes\Rozier\Events;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use RZ\Roadiz\Core\Entities\DocumentTranslation;
use RZ\Roadiz\Core\Events\DocumentEvents;
use RZ\Roadiz\Core\Events\FilterDocumentEvent;
use RZ\Roadiz\Utils\Asset\Packages;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ExifDocumentSubscriber implements EventSubscriberInterface
{
    /**
     * @var Packages
     */
    private $packages;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @param EntityManager $entityManager
     * @param Packages $packages
     * @param LoggerInterface $logger
     */
    public function __construct(
        EntityManager $entityManager,
        Packages $packages,
        LoggerInterface $logger = null
    ) {
        $this->packages = $packages;
        $this->logger = $logger;
        $this->entityManager = $entityManager;
    }

    public static function getSubscribedEvents()
    {
        return [
            DocumentEvents::DOCUMENT_IMAGE_UPLOADED => 'onImageUploaded',
        ];
    }

    /**
     * @param FilterDocumentEvent $event
     */
    public function onImageUploaded(FilterDocumentEvent $event)
    {
        if (function_exists('exif_read_data')) {
            $document = $event->getDocument();
            if ($document->getDocumentTranslations()->count() === 0 &&
                ($document->getMimeType() == 'image/jpeg' || $document->getMimeType() == 'image/tiff')) {
                $filePath = $this->packages->getDocumentFilePath($document);
                $exif = exif_read_data($filePath, 0, false);

                if (false !== $exif) {
                    $copyright = $this->getCopyright($exif);
                    $description = $this->getDescription($exif);

                    if (null !== $copyright || null !== $description) {
                        if (null !== $this->logger) {
                            $this->logger->debug('EXIF information available for document.', ['document' => $document->getFilename()]);
                        }
                        $defaultTranslation = $this->entityManager
                                                   ->getRepository('RZ\Roadiz\Core\Entities\Translation')
                                                   ->findDefault();

                        $documentTranslation = new DocumentTranslation();
                        $documentTranslation->setCopyright($copyright)
                                            ->setDocument($document)
                                            ->setDescription($description)
                                            ->setTranslation($defaultTranslation);

                        $this->entityManager->persist($documentTranslation);
                    }
                }
            }
        }
    }

    /**
     * @param array $exif
     * @return string|null
     */
    protected function getCopyright(array $exif)
    {
        foreach ($exif as $key => $section) {
            if (is_array($section)) {
                foreach ($section as $skey => $value) {
                    if (strtolower($skey) == 'copyright') {
                        return $value;
                    }
                }
            }
        }

        return null;
    }

    /**
     * @param array $exif
     * @return string|null
     */
    protected function getDescription(array $exif)
    {
        foreach ($exif as $key => $section) {
            if (is_array($section)) {
                if (strtolower($key) == 'comment') {
                    $comment = '';
                    foreach ($section as $value) {
                        $comment .= $value . PHP_EOL;
                    }
                    return $comment;
                } else {
                    foreach ($section as $skey => $value) {
                        if (strtolower($skey) == 'comment') {
                            return $value;
                        }
                    }
                }
            } elseif (is_string($section) && strtolower($key) == 'imagedescription') {
                return $section;
            }
        }

        return null;
    }
}
