<?php
declare(strict_types=1);

namespace Themes\Rozier\Events;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Events\DocumentFileUploadedEvent;
use RZ\Roadiz\Core\Models\DocumentInterface;
use RZ\Roadiz\Utils\Asset\Packages;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\File;

class DocumentFilesizeSubscriber implements EventSubscriberInterface
{
    private Packages $packages;
    private LoggerInterface $logger;

    /**
     * @param Packages $packages
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        Packages $packages,
        ?LoggerInterface $logger = null
    ) {
        $this->packages = $packages;
        $this->logger = $logger ?? new NullLogger();
    }

    public static function getSubscribedEvents()
    {
        return [
            DocumentFileUploadedEvent::class => ['onFileUploaded', 0],
        ];
    }

    /**
     * @param DocumentInterface $document
     *
     * @return bool
     */
    protected function supports(DocumentInterface $document)
    {
        if ($document->isLocal() && null !== $document->getRelativePath()) {
            return true;
        }

        return false;
    }

    /**
     * @param DocumentFileUploadedEvent $event
     */
    public function onFileUploaded(DocumentFileUploadedEvent $event)
    {
        $document = $event->getDocument();
        if ($this->supports($document) && $document instanceof Document) {
            $documentPath = $this->packages->getDocumentFilePath($document);
            try {
                $file = new File($documentPath);
                $document->setFilesize($file->getSize());
            } catch (FileNotFoundException $exception) {
                /*
                 * Do nothing
                 * just return 0 width and height
                 */
                $this->logger->warning('Document file not found.', [
                    'id' => $document->getId(),
                    'path' => $documentPath,
                ]);
            }
        }
    }
}
