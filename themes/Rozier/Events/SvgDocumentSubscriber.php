<?php
declare(strict_types=1);

namespace Themes\Rozier\Events;

use enshrined\svgSanitize\Sanitizer;
use Psr\Log\LoggerInterface;
use RZ\Roadiz\Core\Events\DocumentSvgUploadedEvent;
use RZ\Roadiz\Core\Events\FilterDocumentEvent;
use RZ\Roadiz\Utils\Asset\Packages;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SvgDocumentSubscriber implements EventSubscriberInterface
{
    /**
     * @var Packages
     */
    private $packages;
    /**
     * @var LoggerInterface|null
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
            DocumentSvgUploadedEvent::class => 'onSvgUploaded',
        ];
    }

    /**
     * @param FilterDocumentEvent $event
     */
    public function onSvgUploaded(FilterDocumentEvent $event)
    {
        $document = $event->getDocument();
        if (!$document->isLocal()) {
            return;
        }
        $documentPath = $this->packages->getDocumentFilePath($document);

        // Create a new sanitizer instance
        $sanitizer = new Sanitizer();
        $sanitizer->minify(true);

        // Load the dirty svg
        $dirtySVG = file_get_contents($documentPath);
        if (false !== $dirtySVG) {
            file_put_contents($documentPath, $sanitizer->sanitize($dirtySVG));
            if (null !== $this->logger) {
                $this->logger->info('Svg document sanitized.');
            }
        }
    }
}
