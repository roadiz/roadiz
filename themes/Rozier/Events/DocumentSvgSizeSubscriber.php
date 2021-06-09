<?php
declare(strict_types=1);

namespace Themes\Rozier\Events;

use Psr\Log\LoggerInterface;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Events\DocumentSvgUploadedEvent;
use RZ\Roadiz\Core\Events\FilterDocumentEvent;
use RZ\Roadiz\Core\Models\DocumentInterface;
use RZ\Roadiz\Utils\Asset\Packages;
use RZ\Roadiz\Utils\Document\SvgSizeResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DocumentSvgSizeSubscriber implements EventSubscriberInterface
{
    private Packages $packages;
    private ?LoggerInterface $logger;

    /**
     * @param Packages $packages
     * @param LoggerInterface|null $logger
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
            DocumentSvgUploadedEvent::class => ['onImageUploaded', 0],
        ];
    }

    /**
     * @param DocumentInterface $document
     * @return bool
     */
    protected function supports(DocumentInterface $document)
    {
        if ($document->isLocal() && $document->isSvg()) {
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
            try {
                $svgSizeResolver = new SvgSizeResolver($document, $this->packages);
                $document->setImageWidth($svgSizeResolver->getWidth());
                $document->setImageHeight($svgSizeResolver->getHeight());
            } catch (\RuntimeException $exception) {
                $this->logger->error($exception->getMessage());
            }
        }
    }
}
