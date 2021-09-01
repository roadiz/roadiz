<?php
declare(strict_types=1);

namespace Themes\Rozier\Models;

use Pimple\Container;
use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Models\DocumentInterface;
use RZ\Roadiz\Core\Models\HasThumbnailInterface;
use RZ\Roadiz\Document\Renderer\RendererInterface;
use RZ\Roadiz\Utils\UrlGenerators\DocumentUrlGeneratorInterface;

/**
 * @package Themes\Rozier\Models
 */
final class DocumentModel implements ModelInterface
{
    public static array $thumbnailArray = [
        "fit" => "40x40",
        "quality" => 50,
        "sharpen" => 5,
        "inline" => false,
    ];
    public static array $thumbnail80Array = [
        "fit" => "80x80",
        "quality" => 50,
        "sharpen" => 5,
        "inline" => false,
    ];
    public static array $previewArray = [
        "width" => 1440,
        "quality" => 80,
        "inline" => false,
        "embed" => true,
    ];
    public static array $largeArray = [
        "noProcess" => true,
    ];

    private DocumentInterface $document;
    private Container $container;

    /**
     * @param DocumentInterface $document
     * @param Container $container
     */
    public function __construct(DocumentInterface $document, Container $container)
    {
        $this->document = $document;
        $this->container = $container;
    }

    public function toArray()
    {
        $name = (string) $this->document;

        if ($this->document instanceof Document &&
            $this->document->getDocumentTranslations()->first() &&
            $this->document->getDocumentTranslations()->first()->getName()) {
            $name = $this->document->getDocumentTranslations()->first()->getName();
        }
        /** @var RendererInterface $renderer */
        $renderer = $this->container->offsetGet(RendererInterface::class);

        /** @var DocumentUrlGeneratorInterface $documentUrlGenerator */
        $documentUrlGenerator = $this->container->offsetGet('document.url_generator');
        $documentUrlGenerator->setDocument($this->document);
        $hasThumbnail = false;

        if ($this->document instanceof HasThumbnailInterface &&
            $this->document->needsThumbnail() &&
            $this->document->hasThumbnails()) {
            $documentUrlGenerator->setDocument($this->document->getThumbnails()->first());
            $hasThumbnail = true;
        }

        $documentUrlGenerator->setOptions(static::$thumbnail80Array);
        $thumbnail80Url = $documentUrlGenerator->getUrl();

        $documentUrlGenerator->setOptions(static::$previewArray);
        $previewUrl = $documentUrlGenerator->getUrl();

        if ($this->document instanceof AbstractEntity) {
            $id = $this->document->getId();
            $editUrl = $this->container
                ->offsetGet('urlGenerator')
                ->generate('documentsEditPage', [
                    'documentId' => $this->document->getId()
                ]);
        } else {
            $id = null;
            $editUrl = null;
        }

        return [
            'id' => $id,
            'filename' => (string) $this->document,
            'name' => $name,
            'hasThumbnail' => $hasThumbnail,
            'isImage' => $this->document->isImage(),
            'isWebp' => $this->document->getMimeType() === 'image/webp',
            'isVideo' => $this->document->isVideo(),
            'isSvg' => $this->document->isSvg(),
            'isEmbed' => $this->document->isEmbed(),
            'isPdf' => $this->document->isPdf(),
            'isPrivate' => $this->document->isPrivate(),
            'shortType' => $this->document->getShortType(),
            'editUrl' => $editUrl,
            'preview' => $previewUrl,
            'preview_html' => $renderer->render($this->document, static::$previewArray),
            'embedPlatform' => $this->document->getEmbedPlatform(),
            'shortMimeType' => $this->document->getShortMimeType(),
            'thumbnail_80' => $thumbnail80Url,
        ];
    }
}
