<?php
declare(strict_types=1);

namespace Themes\Rozier\Events;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Entities\DocumentTranslation;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Events\DocumentImageUploadedEvent;
use RZ\Roadiz\Core\Events\FilterDocumentEvent;
use RZ\Roadiz\Core\Models\DocumentInterface;
use RZ\Roadiz\Utils\Asset\Packages;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ExifDocumentSubscriber implements EventSubscriberInterface
{
    private Packages $packages;
    private LoggerInterface $logger;
    private EntityManagerInterface $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     * @param Packages $packages
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        Packages $packages,
        ?LoggerInterface $logger = null
    ) {
        $this->packages = $packages;
        $this->logger = $logger ?? new NullLogger();
        $this->entityManager = $entityManager;
    }

    public static function getSubscribedEvents()
    {
        return [
            DocumentImageUploadedEvent::class => ['onImageUploaded', 101], // read EXIF before processing Raw documents
        ];
    }

    /**
     * @param DocumentInterface $document
     *
     * @return bool
     */
    protected function supports(DocumentInterface $document)
    {
        if (!$document->isLocal()) {
            return false;
        }
        if (!function_exists('exif_read_data')) {
            return false;
        }

        if ($document->getEmbedPlatform() !== "") {
            return false;
        }

        if (($document->getMimeType() == 'image/jpeg' || $document->getMimeType() == 'image/tiff') &&
            $document instanceof Document &&
            $document->getDocumentTranslations()->count() === 0) {
            return true;
        }

        return false;
    }

    /**
     * @param FilterDocumentEvent $event
     *
     * @throws \Doctrine\ORM\ORMException
     */
    public function onImageUploaded(FilterDocumentEvent $event)
    {
        $document = $event->getDocument();
        if ($this->supports($document) && function_exists('exif_read_data')) {
            $filePath = $this->packages->getDocumentFilePath($document);
            $exif = @exif_read_data($filePath, null, false);

            if (false !== $exif) {
                $copyright = $this->getCopyright($exif);
                $description = $this->getDescription($exif);

                if (null !== $copyright || null !== $description) {
                    $this->logger->debug('EXIF information available for document.', [
                        'document' => (string) $document
                    ]);
                    $defaultTranslation = $this->entityManager
                                               ->getRepository(Translation::class)
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
            if (is_string($section) && strtolower($key) == 'imagedescription') {
                return $section;
            } elseif (is_array($section)) {
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
            }
        }

        return null;
    }
}
