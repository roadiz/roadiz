<?php
/**
 * Created by PhpStorm.
 * User: adrien
 * Date: 28/03/2017
 * Time: 19:38
 */

namespace Themes\Rozier\Models;


use Pimple\Container;
use RZ\Roadiz\Core\Entities\Document;

class DocumentModel
{
    public static $thumbnailArray;
    /**
     * @var Document
     */
    private $document;
    /**
     * @var Container
     */
    private $container;

    /**
     * DocumentModel constructor.
     * @param Document $document
     * @param Container $container
     */
    public function __construct(Document $document, Container $container)
    {
        $this->document = $document;
        $this->container = $container;

        static::$thumbnailArray = [
            "fit" => "40x40",
            "quality" => 50,
            "inline" => false,
        ];
    }

    public function toArray()
    {
        return [
            'id' => $this->document->getId(),
            'filename' => $this->document->getFilename(),
            'isImage' => $this->document->isImage(),
            'isSvg' => $this->document->isSvg(),
            'isPrivate' => $this->document->isPrivate(),
            'shortType' => $this->document->getShortType(),
            'editUrl' => $this->container->offsetGet('urlGenerator')->generate('documentsEditPage', [
                'documentId' => $this->document->getId()
            ]),
            'thumbnail' => $this->document->getViewer()->getDocumentUrlByArray(static::$thumbnailArray),
            'isEmbed' => $this->document->isEmbed(),
            'embedPlatform' => $this->document->getEmbedPlatform(),
            'shortMimeType' => $this->document->getShortMimeType(),
            'thumbnail_80' => $this->document->getViewer()->getDocumentUrlByArray([
                "fit" => "80x80",
                "quality" => 50,
                "inline" => false,
            ]),
            'html' => $this->container->offsetGet('twig.environment')->render('widgets/documentSmallThumbnail.html.twig', ['document' => $this->document]),
        ];
    }
}
