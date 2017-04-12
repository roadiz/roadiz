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

/**
 * Class DocumentModel.
 *
 * @package Themes\Rozier\Models
 */
class DocumentModel implements ModelInterface
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
