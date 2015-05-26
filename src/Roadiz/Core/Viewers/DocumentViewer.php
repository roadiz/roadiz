<?php
/**
 * Copyright © 2014, Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
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
 * @file DocumentViewer.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Viewers;

use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Kernel;
use Symfony\Component\Routing\Generator\UrlGenerator;

/**
 * DocumentViewer
 */
class DocumentViewer implements ViewableInterface
{
    private $document;
    private $embedFinder;

    /**
     * @return RZ\Roadiz\Core\Entities\Document
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * @param RZ\Roadiz\Core\Entities\Document $document
     */
    public function __construct(Document $document)
    {
        $this->document = $document;
    }

    /**
     * @return Symfony\Component\Translation\Translator.
     */
    public function getTranslator()
    {
        return null;
    }

    /**
     * @return \Twig_Environment
     */
    public function getTwig()
    {
        return Kernel::getService('twig.environment');
    }

    /**
     * Output a document HTML tag according to its Mime type and
     * the arguments array.
     *
     * ## HTML output options
     *
     * - embed (true|false), display an embed as iframe instead of its thumbnail
     * - identifier
     * - class
     * - **alt**: If not filled, it will get the document name, then the document filename
     *
     * ## Images resampling options
     *
     * - width
     * - height
     * - crop ({w}x{h}, for example : 100x200)
     * - fit ({w}x{h}, for example : 100x200)
     * - rotate (1-359 degrees, for example : 90)
     * - grayscale / greyscale (boolean)
     * - quality (1-100)
     * - background (hexadecimal color without #)
     * - progressive (boolean)
     * - noProcess (boolean) : Disable image resample
     *
     * ## Audio / Video options
     *
     * - autoplay
     * - controls
     *
     * @param array $args
     *
     * @return string HTML output
     */
    public function getDocumentByArray($args = null)
    {
        $assignation = [
            'document' => $this->document,
            'url' => $this->getDocumentUrlByArray($args),
        ];

        if (!empty($args['width'])) {
            $assignation['width'] = (int) $args['width'];
        }
        if (!empty($args['height'])) {
            $assignation['height'] = (int) $args['height'];
        }
        /*
         * Use fit value to set html width & height attributes
         */
        if (!empty($args['fit']) &&
            1 === preg_match('#(?<width>[0-9]+)[x:\.](?<height>[0-9]+)#', $args['fit'], $matches)) {
            $assignation['width'] = (int) $matches['width'];
            $assignation['height'] = (int) $matches['height'];
        }
        if (!empty($args['identifier'])) {
            $assignation['identifier'] = $args['identifier'];
        }
        if (!empty($args['class'])) {
            $assignation['class'] = $args['class'];
        }
        if (!empty($args['autoplay'])) {
            $assignation['autoplay'] = (boolean) $args['autoplay'];
        }
        if (!empty($args['controls'])) {
            $assignation['controls'] = (boolean) $args['controls'];
        }
        if (!empty($args['alt'])) {
            $assignation['alt'] = $args['alt'];
        } elseif (false !== $this->document->getDocumentTranslations()->first() &&
            "" != $this->document->getDocumentTranslations()->first()->getName()
        ) {
            $assignation['alt'] = $this->document->getDocumentTranslations()->first()->getName();
        } else {
            $assignation['alt'] = $this->document->getFileName();
        }

        if (isset($args['embed']) &&
            true === $args['embed'] &&
            $this->isEmbedPlatformSupported()) {
            return $this->getEmbedByArray($args);

        } elseif ($this->document->isImage()) {
            return $this->getTwig()->render('documents/image.html.twig', $assignation);
        } elseif ($this->document->isVideo()) {
            $assignation['sources'] = $this->getSourcesFiles();

            return $this->getTwig()->render('documents/video.html.twig', $assignation);
        } elseif ($this->document->isAudio()) {
            $assignation['sources'] = $this->getSourcesFiles();

            return $this->getTwig()->render('documents/audio.html.twig', $assignation);
        } else {
            return 'document.format.unknown';
        }
    }

    public function isEmbedPlatformSupported()
    {
        $handlers = Kernel::getService('document.platforms');

        if ($this->document->isEmbed() &&
            in_array(
                $this->document->getEmbedPlatform(),
                array_keys($handlers)
            )
        ) {
            return true;
        } else {
            return false;
        }
    }

    public function getEmbedFinder()
    {
        if (null === $this->embedFinder) {
            if ($this->isEmbedPlatformSupported()) {
                $handlers = Kernel::getService('document.platforms');
                $class = $handlers[$this->document->getEmbedPlatform()];
                $this->embedFinder = new $class($this->document->getEmbedId());
            } else {
                $this->embedFinder = false;
            }
        }

        return $this->embedFinder;
    }

    /**
     * Output an external media with an iframe according to the arguments array.
     *
     * @param array|null $args
     *
     * @return string
     * @see RZ\Roadiz\Utils\MediaFinders\AbstractEmbedFinder::getIFrame
     */
    public function getEmbedByArray($args = null)
    {
        if ($this->isEmbedPlatformSupported()) {
            return $this->getEmbedFinder()->getIFrame($args);
        } else {
            return false;
        }
    }

    /**
     * Get sources files formats for audio and video documents.
     *
     * This method will search for document which filename is the same
     * except the extension. If you choose an MP4 file, it will look for a OGV and WEBM file.
     *
     * @return array
     */
    public function getSourcesFiles()
    {
        $basename = pathinfo($this->document->getFileName());
        $basename = $basename['filename'];

        $sources = [];

        if ($this->document->isVideo()) {
            $sourcesDocsName = [
                $basename . '.ogg',
                $basename . '.ogv',
                $basename . '.mp4',
                $basename . '.mov',
                $basename . '.webm',
            ];
        } elseif ($this->document->isAudio()) {
            $sourcesDocsName = [
                $basename . '.mp3',
                $basename . '.ogg',
                $basename . '.wav',
            ];
        } else {
            return false;
        }

        $sourcesDocs = Kernel::getService('em')
            ->getRepository("RZ\Roadiz\Core\Entities\Document")
            ->findBy(["filename" => $sourcesDocsName]);

        foreach ($sourcesDocs as $source) {
            $sources[] = [
                'mime' => $source->getMimeType(),
                'url' => Kernel::getInstance()->getRequest()->getBaseUrl() . '/files/' . $source->getRelativeUrl(),
            ];
        }

        return $sources;
    }

    /**
     * Generate a resampled document Url.
     *
     * Generated URL will be **absolute** and **static** if
     * a static domain name has been setup.
     *
     * - width
     * - height
     * - crop ({w}x{h}, for example : 100x200)
     * - fit ({w}x{h}, for example : 100x200)
     * - rotate (1-359 degrees, for example : 90)
     * - grayscale / greyscale (boolean)
     * - quality (1-100) - default: 90
     * - background (hexadecimal color without #)
     * - progressive (boolean)
     * - noProcess (boolean) : Disable image resample
     *
     * @param array $args
     *
     * @return string Url
     */
    public function getDocumentUrlByArray($args = null)
    {
        if ($args === null ||
            (isset($args['noProcess']) && $args['noProcess'] === true) ||
            !$this->document->isImage()) {
            return Kernel::getInstance()->getStaticBaseUrl() . '/files/' . $this->document->getRelativeUrl();
        } else {
            $slirArgs = [];

            if (!empty($args['width'])) {
                $slirArgs['w'] = 'w' . (int) $args['width'];
            }
            if (!empty($args['height'])) {
                $slirArgs['h'] = 'h' . (int) $args['height'];
            }
            if (!empty($args['crop'])) {
                $slirArgs['c'] = 'c' . strip_tags($args['crop']);
            }
            if (!empty($args['fit'])) {
                $slirArgs['f'] = 'f' . strip_tags($args['fit']);
            }
            if (!empty($args['rotate'])) {
                $slirArgs['r'] = 'r' . strip_tags($args['rotate']);
            }
            if ((!empty($args['grayscale']) && $args['grayscale'] === true) ||
                (!empty($args['greyscale']) && $args['greyscale'] === true)) {
                $slirArgs['g'] = 'g1';
            }
            if (!empty($args['quality'])) {
                $slirArgs['q'] = 'q' . (int) $args['quality'];
            } else {
                $slirArgs['q'] = 'q90'; // Set default quality to 90%
            }
            if (!empty($args['background'])) {
                $slirArgs['b'] = 'b' . strip_tags($args['background']);
            }
            if (!empty($args['progressive']) && $args['progressive'] === true) {
                $slirArgs['p'] = 'p1';
            }

            $url = Kernel::getService('urlGenerator')->generate('interventionRequestProcess', [
                'queryString' => implode('-', $slirArgs),
                'filename' => $this->document->getRelativeUrl(),
            ], UrlGenerator::ABSOLUTE_PATH);

            return Kernel::getInstance()->convertUrlToStaticDomainUrl($url);
        }
    }
}
