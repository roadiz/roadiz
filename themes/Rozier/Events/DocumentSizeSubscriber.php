<?php
declare(strict_types=1);

namespace Themes\Rozier\Events;

use Intervention\Image\Exception\NotReadableException;
use Intervention\Image\ImageManager;
use Psr\Log\LoggerInterface;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Events\DocumentImageUploadedEvent;
use RZ\Roadiz\Core\Events\FilterDocumentEvent;
use RZ\Roadiz\Core\Models\DocumentInterface;
use RZ\Roadiz\Utils\Asset\Packages;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DocumentSizeSubscriber implements EventSubscriberInterface
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
     * @param Packages $packages
     * @param LoggerInterface $logger
     */
    public function __construct(
        Packages $packages,
        LoggerInterface $logger = null
    ) {
        $this->packages = $packages;
        $this->logger = $logger;

    }

    public static function getSubscribedEvents()
    {
        return [
            DocumentImageUploadedEvent::class => ['onImageUploaded', 0],
        ];
    }

    /**
     * @param DocumentInterface $document
     *
     * @return bool
     */
    protected function supports(DocumentInterface $document)
    {
        if ($document->isLocal() && $document->isImage()) {
            return true;
        }

        return false;
    }

    /**
     * @param FilterDocumentEvent $event
     */
    public function onImageUploaded(FilterDocumentEvent $event)
    {
        $document = $event->getDocument();
        if ($this->supports($document) && $document instanceof Document) {
            $documentPath = $this->packages->getDocumentFilePath($document);
            try {
                $manager = new ImageManager();
                $imageProcess = $manager->make($documentPath);

                $document->setImageWidth($imageProcess->width());
                $document->setImageHeight($imageProcess->height());
            } catch (NotReadableException $exception) {
                /*
                 * Do nothing
                 * just return 0 width and height
                 */
                $this->logger->warning('Document file is not a readable image.', [
                    'id' => $document->getId(),
                    'path' => $documentPath,
                ]);
            }
        }
    }
}
