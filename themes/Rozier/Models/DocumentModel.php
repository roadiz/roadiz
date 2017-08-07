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
 * @file DocumentModel.php
 * @author Adrien Scholaert <adrien@rezo-zero.com>
 */

namespace Themes\Rozier\Models;

use Pimple\Container;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Viewers\DocumentViewer;
use RZ\Roadiz\Utils\UrlGenerators\DocumentUrlGenerator;

/**
 * Class DocumentModel.
 *
 * @package Themes\Rozier\Models
 */
class DocumentModel implements ModelInterface
{
    public static $thumbnailArray;
    public static $thumbnail80Array;
    public static $previewArray;
    public static $largeArray;

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
    }

    public function toArray()
    {
        $name = $this->document->getFilename();

        if ($this->document->getDocumentTranslations()->first() &&
            $this->document->getDocumentTranslations()->first()->getName()) {
            $name = $this->document->getDocumentTranslations()->first()->getName();
        }
        /** @var DocumentViewer $documentViewer */
        $documentViewer = $this->container->offsetGet('document.viewer');
        $documentViewer->setDocument($this->document);

        /** @var DocumentUrlGenerator $documentUrlGenerator */
        $documentUrlGenerator = $this->container->offsetGet('document.url_generator');
        $documentUrlGenerator->setDocument($this->document);

        $documentUrlGenerator->setOptions(static::$thumbnailArray);
        $thumbnailUrl = $documentUrlGenerator->getUrl();

        $documentUrlGenerator->setOptions(static::$thumbnail80Array);
        $thumbnail80Url = $documentUrlGenerator->getUrl();

        $documentUrlGenerator->setOptions(static::$previewArray);
        $previewUrl = $documentUrlGenerator->getUrl();

        $documentUrlGenerator->setOptions(static::$largeArray);
        $largeUrl = $documentUrlGenerator->getUrl();

        return [
            'id' => $this->document->getId(),
            'filename' => $this->document->getFilename(),
            'name' => $name,
            'isImage' => $this->document->isImage(),
            'isVideo' => $this->document->isVideo(),
            'isSvg' => $this->document->isSvg(),
            'isEmbed' => $this->document->isEmbed(),
            'isPdf' => $this->document->isPdf(),
            'isPrivate' => $this->document->isPrivate(),
            'shortType' => $this->document->getShortType(),
            'editUrl' => $this->container->offsetGet('urlGenerator')->generate('documentsEditPage', [
                'documentId' => $this->document->getId()
            ]),
            'thumbnail' => $thumbnailUrl,
            'large' => $largeUrl,
            'preview' => $previewUrl,
            'preview_html' => $documentViewer->getDocumentByArray(static::$previewArray),
            'embedPlatform' => $this->document->getEmbedPlatform(),
            'shortMimeType' => $this->document->getShortMimeType(),
            'thumbnail_80' => $thumbnail80Url,
            'html' => $this->container->offsetGet('twig.environment')->render('widgets/documentSmallThumbnail.html.twig', ['document' => $this->document]),
        ];
    }
}
DocumentModel::$thumbnailArray = [
    "fit" => "40x40",
    "quality" => 50,
    "inline" => false,
];

DocumentModel::$thumbnail80Array = [
    "fit" => "80x80",
    "quality" => 50,
    "inline" => false,
];

DocumentModel::$previewArray = [
    "width" => 1440,
    "quality" => 80,
    "inline" => false,
    "embed" => true,
];

DocumentModel::$largeArray = [
    "noProcess" => true,
];
