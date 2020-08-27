<?php
declare(strict_types=1);

namespace Themes\Rozier\Events;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use RZ\Roadiz\Core\Events\DocumentImageUploadedEvent;
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
     * @param LoggerInterface|null $logger
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
            DocumentImageUploadedEvent::class => ['onImageUploaded', 100],
        ];
    }

    public function onImageUploaded(FilterDocumentEvent $event)
    {
        if (null !== $event->getDocument() && $event->getDocument()->isProcessable()) {
            $this->manager->processAndOverrideDocument($event->getDocument());
        }
    }
}
