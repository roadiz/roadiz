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
            'document' => $this->document,
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
        } elseif (
            $this->document->getDocumentTranslations()->count() &&
            "" != $this->document->getDocumentTranslations()->first()->getName()
        ) {
            $assignation['alt'] = $this->document->getDocumentTranslations()->first()->getName();
        } else {
            $assignation['alt'] = $this->document->getFileName();
        }

        if (isset($args['embed']) &&
            true === $args['embed'] &&
            $this->document->isEmbed()) {

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


    public function getEmbedFinder()
    {
        if (null === $this->embedFinder) {

            $handlers = Kernel::getService('document.platforms');

            if (in_array(
                $this->document->getEmbedPlatform(),
                array_keys($handlers)
            )) {

                $class = $handlers[$this->document->getEmbedPlatform()];
                $this->embedFinder = new $class($this->document->getEmbedId());

            } else {
                throw new EmbedPlatformNotSupportedException(
                    "“".$this->document->getEmbedPlatform()."” is not a supported platform."
                );
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
     * @see RZ\Renzo\Core\Utils\AbstractEmbedFinder::getIFrame
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
        $basename = pathinfo($this->document->getFileName());
        $basename = $basename['filename'];

        $sources = array();

        if ($this->document->isVideo()) {
            $sourcesDocsName = array(
                $basename . '.ogg',
                $basename . '.ogv',
                $basename . '.mp4',
                $basename . '.mov',
                $basename . '.webm'
            );
        } elseif ($this->document->isAudio()) {
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
            !$this->document->isImage()) {

            return Kernel::getInstance()->getRequest()
                                        ->getBaseUrl().'/files/'.$this->document->getRelativeUrl();
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
                'filename' => $this->document->getRelativeUrl()
            ));
        }
    }
}
