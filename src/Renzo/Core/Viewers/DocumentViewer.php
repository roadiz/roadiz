<?php
/*
 * Copyright REZO ZERO 2014
 *
 * Description
 *
 * @file DocumentViewer.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */

namespace RZ\Renzo\Core\Viewers;

use RZ\Renzo\Core\Entities\Document;
use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Exceptions\EmbedPlatformNotSupportedException;

use Symfony\Bridge\Twig\Extension\RoutingExtension;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Bridge\Twig\Form\TwigRenderer;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;

use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\Loader\XliffFileLoader;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

/**
 * DocumentViewer
 */
class DocumentViewer implements ViewableInterface
{
    /**
     * We use a static Twig instance
     * for performance issue, not to recreate it at
     * each `getDocumentByArray` call.
     */
    protected static $twig = null;

    private $document;
    private $embedFinder;

    /**
     * @return RZ\Renzo\Core\Entities\Document
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * @param RZ\Renzo\Core\Entities\Document $document
     */
    public function __construct(Document $document)
    {
        $this->initializeTwig();
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
     * Get twig cache folder for current Viewer.
     * @return string
     */
    public function getCacheDirectory()
    {
        return RENZO_ROOT.'/cache/Core/DocumentViewer/twig_cache';
    }

    /**
     * Create a Twig Environment instance
     *
     * @return \Twig_Loader_Filesystem
     */
    public function initializeTwig()
    {
        if (null === static::$twig) {

            $loader = new \Twig_Loader_Filesystem(array(
                RENZO_ROOT . '/src/Renzo/Core/Resources/views',
            ));
            static::$twig = new \Twig_Environment($loader, array(
                'cache' => $this->getCacheDirectory(),
                'debug' => Kernel::getInstance()->isDebug()
            ));

            //RoutingExtension
            static::$twig->addExtension(
                new RoutingExtension(Kernel::getService('urlGenerator'))
            );
        }

        return $this;
    }

    /**
     * @return \Twig_Environment
     */
    public function getTwig()
    {
        return static::$twig;
    }

    /**
     * Create a translator instance and load theme messages.
     *
     * src/Renzo/Core/Resources/translations/messages.{{lang}}.xlf
     *
     * @todo  [Cache] Need to write XLF catalog to PHP using \Symfony\Component\Translation\Writer\TranslationWriter
     *
     * @return Symfony\Component\Translation\Translator
     */
    public function initializeTranslator()
    {
        return $this;
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
     * - grayscale / greyscale (boolean)
     * - quality (1-100)
     * - background (hexadecimal color without #)
     * - progressive (boolean)
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
        $assignation = array(
            'document' => $this->getDocument(),
            'url' => $this->getDocumentUrlByArray($args)
        );

        if (!empty($args['width'])) {
            $assignation['width'] = (int) $args['width'];
        }
        if (!empty($args['heigth'])) {
            $assignation['heigth'] = (int) $args['heigth'];
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
        } elseif ("" != $this->getDocument()->getName()) {
            $assignation['alt'] = $this->getDocument()->getName();
        } else {
            $assignation['alt'] = $this->getDocument()->getFileName();
        }

        if (isset($args['embed']) &&
            true === $args['embed'] &&
            $this->getDocument()->isEmbed()) {

            return $this->getEmbedByArray($args);

        } elseif ($this->getDocument()->isImage()) {
            return $this->getTwig()->render('documents/image.html.twig', $assignation);
        } elseif ($this->getDocument()->isVideo()) {
            $assignation['sources'] = $this->getSourcesFiles();

            return $this->getTwig()->render('documents/video.html.twig', $assignation);
        } elseif ($this->getDocument()->isAudio()) {
            $assignation['sources'] = $this->getSourcesFiles();

            return $this->getTwig()->render('documents/audio.html.twig', $assignation);
        } else {
            return 'document.format.unknown';
        }
    }


    public function getEmbedFinder()
    {
        if (null === $this->embedFinder) {

            $handlers = Kernel::getService('document.platforms');

            if (in_array($this->getDocument()->getEmbedPlatform(),
                array_keys($handlers))) {

                $class = $handlers[$this->getDocument()->getEmbedPlatform()];
                $this->embedFinder = new $class($this->getDocument()->getEmbedId());

            } else {
                throw new EmbedPlatformNotSupportedException(
                    "“".$this->getDocument()->getEmbedPlatform()."” is not a supported platform."
                );
            }
        }

        return $this->embedFinder;
    }

    /**
     *
     * @param array|null $args
     *
     * @return string
     */
    public function getEmbedByArray($args = null)
    {
        return $this->getEmbedFinder()->getIFrame($args);
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
        $basename = pathinfo($this->getDocument()->getFileName());
        $basename = $basename['filename'];

        $sources = array();

        if ($this->getDocument()->isVideo()) {
            $sourcesDocsName = array(
                $basename . '.ogg',
                $basename . '.ogv',
                $basename . '.mp4',
                $basename . '.mov',
                $basename . '.webm'
            );
        } elseif ($this->getDocument()->isAudio()) {
            $sourcesDocsName = array(
                $basename . '.mp3',
                $basename . '.ogg',
                $basename . '.wav'
            );
        } else {
            return false;
        }

        $sourcesDocs = Kernel::getService('em')
            ->getRepository("RZ\Renzo\Core\Entities\Document")
            ->findBy(array("filename" => $sourcesDocsName));

        foreach ($sourcesDocs as $source) {
            $sources[] = array(
                'mime' => $source->getMimeType(),
                'url' => Kernel::getInstance()->getRequest()->getBaseUrl().'/files/'.$source->getRelativeUrl()
            );
        }

        return $sources;
    }

    /**
     * Generate a resampled document Url.
     *
     * - width
     * - height
     * - crop ({w}x{h}, for example : 100x200)
     * - grayscale / greyscale (boolean)
     * - quality (1-100)
     * - background (hexadecimal color without #)
     * - progressive (boolean)
     *
     * @param array $args
     *
     * @return string Url
     */
    public function getDocumentUrlByArray($args = null)
    {
        if ($args === null ||
            !$this->getDocument()->isImage()) {

            return Kernel::getInstance()->getRequest()->getBaseUrl().'/files/'.$this->getDocument()->getRelativeUrl();
        } else {
            $slirArgs = array();

            if (!empty($args['width'])) {
                $slirArgs['w'] = 'w'.(int) $args['width'];
            }
            if (!empty($args['height'])) {
                $slirArgs['h'] = 'h'.(int) $args['height'];
            }
            if (!empty($args['crop'])) {
                $slirArgs['c'] = 'c'.strip_tags($args['crop']);
            }
            if ((!empty($args['grayscale']) && $args['grayscale'] == true) ||
                (!empty($args['greyscale']) && $args['greyscale'] == true)) {
                $slirArgs['g'] = 'g1';
            }
            if (!empty($args['quality'])) {
                $slirArgs['q'] = 'q'.(int) $args['quality'];
            }
            if (!empty($args['background'])) {
                $slirArgs['b'] = 'b'.strip_tags($args['background']);
            }
            if (!empty($args['progressive']) && $args['progressive'] == true) {
                $slirArgs['p'] = 'p1';
            }

            return Kernel::getService('urlGenerator')->generate('SLIRProcess', array(
                'queryString' => implode('-', $slirArgs),
                'filename' => $this->getDocument()->getRelativeUrl()
            ));
        }
    }
}
