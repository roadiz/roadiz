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
use RZ\Roadiz\Utils\Asset\Packages;
use RZ\Roadiz\Utils\Document\ViewOptionsResolver;
use RZ\Roadiz\Utils\MediaFinders\AbstractEmbedFinder;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\Routing\Generator\UrlGenerator;

/**
 * Class DocumentViewer
 * @package RZ\Roadiz\Core\Viewers
 */
class DocumentViewer implements ViewableInterface
{
    private $document;
    private $embedFinder;

    /**
     * @return \RZ\Roadiz\Core\Entities\Document
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * @param \RZ\Roadiz\Core\Entities\Document $document
     */
    public function __construct(Document $document)
    {
        $this->document = $document;
    }

    /**
     * @return \Symfony\Component\Translation\Translator.
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
     *
     * @param  array   $options
     * @return string
     */
    protected function parseSrcSet(array &$options = [])
    {
        if (count($options['srcset']) > 0) {
            $srcset = [];
            foreach ($options['srcset'] as $key => $set) {
                if (isset($set['format']) && isset($set['rule'])) {
                    $srcset[] = $this->getDocumentUrlByArray($set['format'], $options['absolute']) . ' ' . $set['rule'];
                }
            }
            return implode(', ', $srcset);
        }

        return false;
    }

    /**
     *
     * @param  array  $options sizes
     * @return string
     */
    protected function parseSizes(array &$options = [])
    {
        if (count($options['sizes']) > 0) {
            return implode(', ', $options['sizes']);
        }

        return false;
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
     * - lazyload (true | false) set src in data-src
     * - crop ({w}x{h}, for example : 100x200)
     * - fit ({w}x{h}, for example : 100x200)
     * - rotate (1-359 degrees, for example : 90)
     * - grayscale (boolean)
     * - quality (1-100)
     * - blur (1-100)
     * - sharpen (1-100)
     * - contrast (1-100)
     * - background (hexadecimal color without #)
     * - progressive (boolean)
     * - noProcess (boolean) : Disable image resample
     * - inline : For SVG, display SVG code in Html instead of using <object>
     * - srcset : Array
     *     [
     *         - format: Array (same options as image)
     *         - rule
     *     ]
     * - sizes : Array
     *     [
     *         - "size1"
     *         - "size2"
     *     ]
     *
     * ## Audio / Video options
     *
     * - autoplay
     * - loop
     * - controls
     * - custom_poster
     *
     * For videos, a poster can be set if you name a document after your video filename (without extension).
     *
     * @param array $options
     *
     * @return string HTML output
     */
    public function getDocumentByArray(array $options = [])
    {
        $resolver = new ViewOptionsResolver();
        $options = $resolver->resolve($options);

        $assignation = [
            'document' => $this->document,
            'url' => $this->getDocumentUrlByArray($options, $options['absolute']),
            'srcset' => $this->parseSrcSet($options),
            'sizes' => $this->parseSizes($options),
        ];

        $assignation['lazyload'] = $options['lazyload'];
        $assignation['autoplay'] = $options['autoplay'];
        $assignation['loop'] = $options['loop'];
        $assignation['controls'] = $options['controls'];

        if ($options['width'] > 0) {
            $assignation['width'] = $options['width'];
        }
        if ($options['height'] > 0) {
            $assignation['height'] = $options['height'];
        }

        if (!empty($options['identifier'])) {
            $assignation['identifier'] = $options['identifier'];
            $assignation['id'] = $options['identifier'];
        }

        if (!empty($options['class'])) {
            $assignation['class'] = $options['class'];
        }

        if (!empty($options['alt'])) {
            $assignation['alt'] = $options['alt'];
        } elseif (false !== $this->document->getDocumentTranslations()->first() &&
            "" != $this->document->getDocumentTranslations()->first()->getName()
        ) {
            $assignation['alt'] = $this->document->getDocumentTranslations()->first()->getName();
        } else {
            $assignation['alt'] = $this->document->getFilename();
        }

        if ($options['embed'] &&
            $this->isEmbedPlatformSupported()) {
            return $this->getEmbedByArray($options);
        } elseif ($this->document->isSvg()) {
            try {
                /** @var Packages $packages */
                $packages = Kernel::getService('assetPackages');
                $asObject = !$options['inline'];
                $viewer = new SvgDocumentViewer(
                    $packages->getDocumentFilePath($this->document),
                    $assignation,
                    $asObject,
                    $packages->getUrl($this->document->getRelativeUrl(), Packages::DOCUMENTS)
                );
                return $viewer->getContent();
            } catch (FileNotFoundException $e) {
                return false;
            }
        } elseif ($this->document->isImage()) {
            return $this->getTwig()->render('documents/image.html.twig', $assignation);
        } elseif ($this->document->isVideo()) {
            $assignation['sources'] = $this->getSourcesFiles();

            /*
             * Use a user defined poster url
             */
            if (!empty($options['custom_poster'])) {
                $assignation['custom_poster'] = trim(strip_tags($options['custom_poster']));
            } else {
                /*
                 * Look for poster with the same args as the video.
                 */
                $assignation['poster'] = $this->getPosterFile($options, $options['absolute']);
            }
            return $this->getTwig()->render('documents/video.html.twig', $assignation);
        } elseif ($this->document->isAudio()) {
            $assignation['sources'] = $this->getSourcesFiles();
            return $this->getTwig()->render('documents/audio.html.twig', $assignation);
        } elseif ($this->document->isPdf()) {
            return $this->getTwig()->render('documents/pdf.html.twig', $assignation);
        } else {
            return 'document.format.unknown';
        }
    }

    /**
     * @return bool
     */
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

    /**
     * @return bool|AbstractEmbedFinder
     */
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
     * @param array|null $options
     *
     * @return string
     * @see \RZ\Roadiz\Utils\MediaFinders\AbstractEmbedFinder::getIFrame
     */
    protected function getEmbedByArray(array $options = [])
    {
        if ($this->isEmbedPlatformSupported()) {
            return $this->getEmbedFinder()->getIFrame($options);
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
     * @return array|bool
     */
    protected function getSourcesFiles()
    {
        $basename = pathinfo($this->document->getFilename());
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
            ->getRepository('RZ\Roadiz\Core\Entities\Document')
            ->findBy(["filename" => $sourcesDocsName]);

        /** @var Document $source */
        foreach ($sourcesDocs as $source) {
            $sources[] = [
                'mime' => $source->getMimeType(),
                'url' => Kernel::getService('assetPackages')->getUrl($source->getRelativeUrl(), Packages::DOCUMENTS),
            ];
        }

        return $sources;
    }

    /**
     * @param array $options
     * @param bool $absolute
     * @return array|bool
     */
    protected function getPosterFile($options = [], $absolute = false)
    {
        if ($this->document->isVideo()) {
            $basename = pathinfo($this->document->getFilename());
            $basename = $basename['filename'];

            $sourcesDocsName = [
                $basename . '.jpg',
                $basename . '.gif',
                $basename . '.png',
                $basename . '.jpeg',
                $basename . '.webp',
            ];

            $sourcesDoc = Kernel::getService('em')
                ->getRepository('RZ\Roadiz\Core\Entities\Document')
                ->findOneBy([
                    "filename" => $sourcesDocsName,
                    "raw" => false,
                ]);

            if (null !== $sourcesDoc && $sourcesDoc instanceof Document) {
                return [
                    'mime' => $sourcesDoc->getMimeType(),
                    'url' => $sourcesDoc->getViewer()->getDocumentUrlByArray($options, $absolute),
                ];
            }
        }

        return false;
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
     * - grayscale (boolean)
     * - quality (1-100) - default: 90
     * - blur (1-100)
     * - sharpen (1-100)
     * - contrast (1-100)
     * - background (hexadecimal color without #)
     * - progressive (boolean)
     * - noProcess (boolean) : Disable image resample
     *
     * @param array $options
     * @param boolean $absolute
     *
     * @return string Url
     */
    public function getDocumentUrlByArray(array $options = [], $absolute = false)
    {
        $resolver = new ViewOptionsResolver();
        $options = $resolver->resolve($options);

        if ($options['noProcess'] === true || !$this->document->isImage()) {
            $documentPackageName = $absolute ? Packages::ABSOLUTE_DOCUMENTS : Packages::DOCUMENTS;
            return Kernel::getService('assetPackages')->getUrl(
                $this->document->getRelativeUrl(),
                $documentPackageName
            );
        }

        $defaultPackageName = $absolute ? Packages::ABSOLUTE : null;
        return Kernel::getService('assetPackages')->getUrl(
            $this->getProcessedDocumentUrlByArray($options, $absolute),
            $defaultPackageName
        );
    }

    /**
     * @param array $options
     * @param bool $absolute
     * @return string
     */
    protected function getProcessedDocumentUrlByArray(array &$options = [], $absolute = false)
    {
        $interventionRequestOptions = [];

        if (null === $options['fit'] && $options['width'] > 0) {
            $interventionRequestOptions['w'] = 'w' . (int) $options['width'];
        }
        if (null === $options['fit'] && $options['height'] > 0) {
            $interventionRequestOptions['h'] = 'h' . (int) $options['height'];
        }
        if (null !== $options['crop']) {
            $interventionRequestOptions['c'] = 'c' . strip_tags($options['crop']);
        }
        if ($options['blur'] > 0) {
            $interventionRequestOptions['l'] = 'l' . ($options['blur']);
        }
        if (null !== $options['fit']) {
            $interventionRequestOptions['f'] = 'f' . strip_tags($options['fit']);
        }
        if ($options['rotate'] > 0) {
            $interventionRequestOptions['r'] = 'r' . ($options['rotate']);
        }
        if ($options['sharpen'] > 0) {
            $interventionRequestOptions['s'] = 's' . ($options['sharpen']);
        }
        if ($options['contrast'] > 0) {
            $interventionRequestOptions['k'] = 'k' . ($options['contrast']);
        }
        if ($options['grayscale']) {
            $interventionRequestOptions['g'] = 'g1';
        }
        if ($options['quality'] > 0) {
            $interventionRequestOptions['q'] = 'q' . $options['quality'];
        }
        if (null !== $options['background']) {
            $interventionRequestOptions['b'] = 'b' . strip_tags($options['background']);
        }
        if ($options['progressive']) {
            $interventionRequestOptions['p'] = 'p1';
        }

        $routeParams = [
            'queryString' => implode('-', $interventionRequestOptions),
            'filename' => $this->document->getRelativeUrl(),
        ];
        
        return Kernel::getService('urlGenerator')->generate(
            'interventionRequestProcess',
            $routeParams,
            UrlGenerator::ABSOLUTE_PATH
        );
    }
}
