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

use Doctrine\ORM\EntityManager;
use Pimple\Container;
use RZ\Roadiz\Utils\UrlGenerators\DocumentUrlGenerator;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Utils\Asset\Packages;
use RZ\Roadiz\Utils\Document\ViewOptionsResolver;
use RZ\Roadiz\Utils\MediaFinders\AbstractEmbedFinder;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class DocumentViewer
 * @package RZ\Roadiz\Core\Viewers
 */
class DocumentViewer
{
    /** @var null|Document */
    protected $document;

    protected $embedFinder;

    /** @var Packages  */
    protected $packages;

    /** @var RequestStack */
    protected $requestStack;

    /** @var \Twig_Environment */
    protected $twig;

    /** @var EntityManager */
    protected $entityManager;

    /** @var array */
    protected $documentPlatforms;

    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    /** @var Container  */
    private $container;

    /**
     * @param Document|null $document
     */
    public function __construct(Document $document = null)
    {
        $this->document = $document;
        $this->packages = Kernel::getService('assetPackages');
        $this->requestStack = Kernel::getService('requestStack');
        $this->twig = Kernel::getService('twig.environment');
        $this->entityManager = Kernel::getService('em');
        $this->documentPlatforms = Kernel::getService('document.platforms');
        $this->urlGenerator = Kernel::getService('urlGenerator');
        $this->container = Kernel::getInstance()->getContainer();
    }

    /**
     * @return null|Document
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * @param Document $document
     * @return DocumentViewer
     */
    public function setDocument(Document $document)
    {
        $this->document = $document;
        return $this;
    }

    /**
     * @return Packages
     */
    public function getPackages()
    {
        return $this->packages;
    }

    /**
     * @param Packages $packages
     * @return DocumentViewer
     */
    public function setPackages(Packages $packages)
    {
        $this->packages = $packages;
        return $this;
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
            foreach ($options['srcset'] as $set) {
                if (isset($set['format']) && isset($set['rule'])) {
                    /** @var DocumentUrlGenerator $documentUrlGenerator */
                    $documentUrlGenerator = $this->container->offsetGet('document.url_generator');
                    $documentUrlGenerator->setOptions($set['format']);
                    $documentUrlGenerator->setDocument($this->document);
                    $srcset[] = $documentUrlGenerator->getUrl($options['absolute']) . ' ' . $set['rule'];
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

        /** @var DocumentUrlGenerator $documentUrlGenerator */
        $documentUrlGenerator = $this->container->offsetGet('document.url_generator');
        $documentUrlGenerator->setOptions($options);
        $documentUrlGenerator->setDocument($this->document);

        $assignation = [
            'document' => $this->document,
            'url' => $documentUrlGenerator->getUrl($options['absolute']),
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
                $packages = $this->getPackages();
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
            return $this->twig->render('documents/image.html.twig', $assignation);
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
            return $this->twig->render('documents/video.html.twig', $assignation);
        } elseif ($this->document->isAudio()) {
            $assignation['sources'] = $this->getSourcesFiles();
            return $this->twig->render('documents/audio.html.twig', $assignation);
        } elseif ($this->document->isPdf()) {
            return $this->twig->render('documents/pdf.html.twig', $assignation);
        } else {
            return 'document.format.unknown';
        }
    }

    /**
     * @return bool
     */
    public function isEmbedPlatformSupported()
    {
        if ($this->document->isEmbed() &&
            in_array(
                $this->document->getEmbedPlatform(),
                array_keys($this->documentPlatforms)
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
                $class = $this->documentPlatforms[$this->document->getEmbedPlatform()];
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
     * @return string|boolean
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

        $sourcesDocs = $this->entityManager
            ->getRepository('RZ\Roadiz\Core\Entities\Document')
            ->findBy(["filename" => $sourcesDocsName]);

        /** @var Document $source */
        foreach ($sourcesDocs as $source) {
            $sources[$source->getMimeType()] = [
                'mime' => $source->getMimeType(),
                'url' => $this->getPackages()->getUrl($source->getRelativeUrl(), Packages::DOCUMENTS),
            ];
        }

        krsort($sources);

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

            $sourcesDoc = $this->entityManager
                ->getRepository('RZ\Roadiz\Core\Entities\Document')
                ->findOneBy([
                    "filename" => $sourcesDocsName,
                    "raw" => false,
                ]);

            if (null !== $sourcesDoc && $sourcesDoc instanceof Document) {
                /** @var DocumentUrlGenerator $documentUrlGenerator */
                $documentUrlGenerator = $this->container->offsetGet('document.url_generator');
                $documentUrlGenerator->setOptions($options);
                $documentUrlGenerator->setDocument($sourcesDoc);
                return [
                    'mime' => $sourcesDoc->getMimeType(),
                    'url' => $documentUrlGenerator->getUrl($absolute),
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
     * @deprecated Use DocumentUrlGenerator class
     * @return string Url
     */
    public function getDocumentUrlByArray(array $options = [], $absolute = false)
    {
        $resolver = new ViewOptionsResolver();
        $options = $resolver->resolve($options);

        if ($options['noProcess'] === true || !$this->document->isImage()) {
            $documentPackageName = $absolute ? Packages::ABSOLUTE_DOCUMENTS : Packages::DOCUMENTS;
            return $this->getPackages()->getUrl(
                $this->document->getRelativeUrl(),
                $documentPackageName
            );
        }

        $defaultPackageName = $absolute ? Packages::ABSOLUTE : null;
        return $this->getPackages()->getUrl(
            $this->getProcessedDocumentUrlByArray($options),
            $defaultPackageName
        );
    }

    /**
     * @param array $options
     * @deprecated Use DocumentUrlGenerator class
     * @return string
     */
    protected function getProcessedDocumentUrlByArray(array &$options = [])
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

        $path = $this->urlGenerator->generate(
            'interventionRequestProcess',
            $routeParams,
            UrlGeneratorInterface::ABSOLUTE_PATH
        );

        /*
         * Need to remove base-path from url as AssetPackages will prepend it.
         */
        $path = $this->removeBasePath($path);

        return $this->removeStartingSlash($path);
    }

    /**
     * Need to remove base-path from url as AssetPackages will prepend it.
     *
     * @param string $path
     * @deprecated Use DocumentUrlGenerator class
     * @return bool|string
     */
    protected function removeBasePath($path)
    {
        $basePath = $this->requestStack->getMasterRequest()->getBasePath();
        if ($basePath != '') {
            $path = substr($path, strlen($basePath));
        }

        return $path;
    }

    /**
     * Remove root-slash not to disable Assets Packages resolving
     * real server root.
     *
     * @param string $path
     * @deprecated Use DocumentUrlGenerator class
     * @return string
     */
    protected function removeStartingSlash($path)
    {
        if (substr($path, 0, 1) === '/') {
            $path = substr($path, 1);
        }

        return $path;
    }
}
