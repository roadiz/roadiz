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
 * @file Document.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Intervention\Image\ImageManager;
use RZ\Roadiz\Core\AbstractEntities\AbstractDateTimed;
use RZ\Roadiz\Utils\StringHandler;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Documents entity represent a file on server with datetime and naming.
 *
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\DocumentRepository")
 * @ORM\Table(name="documents", indexes={
 *     @ORM\Index(columns={"raw"}),
 *     @ORM\Index(columns={"private"})
 * })
 */
class Document extends AbstractDateTimed
{
    /**
     * Associate mime type to simple types.
     *
     * - code
     * - image
     * - word
     * - video
     * - audio
     * - pdf
     * - archive
     * - excel
     * - powerpoint
     * - font
     *
     * @var array
     */
    public static $mimeToIcon = [
        'text/html' => 'code',
        'application/javascript' => 'code',
        'text/css' => 'code',
        'text/rtf' => 'word',
        'text/xml' => 'code',
        'image/png' => 'image',
        'image/jpeg' => 'image',
        'image/gif' => 'image',
        'image/tiff' => 'image',
        'application/pdf' => 'pdf',
        // Audio types
        'audio/mpeg' => 'audio',
        'audio/x-wav' => 'audio',
        'audio/wav' => 'audio',
        'audio/aac' => 'audio',
        'audio/mp4' => 'audio',
        'audio/webm' => 'audio',
        'audio/ogg' => 'audio',
        'audio/vorbis' => 'audio',
        'audio/ac3' => 'audio',
        // Video types
        'application/ogg' => 'video',
        'video/ogg' => 'video',
        'video/webm' => 'video',
        'video/mpeg' => 'video',
        'video/mp4' => 'video',
        'video/x-m4v' => 'video',
        'video/quicktime' => 'video',
        'video/x-flv' => 'video',
        'video/3gpp' => 'video',
        'video/3gpp2' => 'video',
        'video/3gpp-tt' => 'video',
        'video/VP8' => 'video',
        // Epub type
        'application/epub+zip' => 'epub',
        // Archives types
        'application/gzip' => 'archive',
        'application/zip' => 'archive',
        'application/x-bzip2' => 'archive',
        'application/x-tar' => 'archive',
        'application/x-7z-compressed' => 'archive',
        'application/x-apple-diskimage' => 'archive',
        'application/x-rar-compressed' => 'archive',
        // Office types
        'application/msword' => 'word',
        'application/vnd.ms-excel' => 'excel',
        'application/vnd.ms-office' => 'excel',
        'application/vnd.ms-powerpoint' => 'powerpoint',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'word',
        'application/vnd.oasis.opendocument.text ' => 'word',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.template' => 'excel',
        'application/vnd.oasis.opendocument.spreadsheet' => 'excel',
        'application/vnd.openxmlformats-officedocument.presentationml.slideshow' => 'powerpoint',
        'application/vnd.oasis.opendocument.presentation' => 'powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'powerpoint',
        // Fonts types
        'image/svg+xml' => 'font',
        'application/x-font-ttf' => 'font',
        'application/x-font-truetype' => 'font',
        'application/x-font-opentype' => 'font',
        'application/font-woff' => 'font',
        'application/vnd.ms-fontobject' => 'font',
        'font/opentype' => 'font',
        'font/ttf' => 'font',
    ];

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $filename;
    /**
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }
    /**
     * @param string $filename
     *
     * @return $this
     */
    public function setFilename($filename)
    {
        $this->filename = StringHandler::cleanForFilename($filename);

        return $this;
    }

    /**
     * @ORM\Column(name="mime_type", type="string", nullable=true)
     */
    private $mimeType;
    /**
     * @return string
     */
    public function getMimeType()
    {
        return $this->mimeType;
    }
    /**
     * @param string $mimeType
     *
     * @return $this
     */
    public function setMimeType($mimeType)
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    /**
     * @ORM\OneToOne(targetEntity="Document", inversedBy="downscaledDocument", cascade={"all"})
     * @ORM\JoinColumn(name="raw_document", referencedColumnName="id", onDelete="CASCADE")
     **/
    protected $rawDocument = null;

    /**
     * @ORM\OneToOne(targetEntity="Document", mappedBy="rawDocument")
     **/
    private $downscaledDocument = null;

    /**
     * @ORM\Column(type="boolean", name="raw", nullable=false, options={"default" = false})
     */
    protected $raw = false;

    /**
     * Get short type name for current document Mime type.
     *
     * @return string
     */
    public function getShortType()
    {
        if (isset(static::$mimeToIcon[$this->getMimeType()])) {
            return static::$mimeToIcon[$this->getMimeType()];
        } else {
            return 'unknown';
        }
    }

    /**
     * Get short Mime type.
     *
     * @return string
     */
    public function getShortMimeType()
    {
        $mime = explode('/', $this->getMimeType());
        return $mime[count($mime) - 1];
    }

    /**
     * Is current document an image.
     *
     * @return boolean
     */
    public function isImage()
    {
        return isset(static::$mimeToIcon[$this->getMimeType()]) && static::$mimeToIcon[$this->getMimeType()] == 'image';
    }

    /**
     * Is current document a vector SVG file.
     *
     * @return boolean
     */
    public function isSvg()
    {
        return $this->getMimeType() == 'image/svg+xml' || $this->getMimeType() == 'image/svg';
    }

    /**
     * Is current document a video.
     *
     * @return boolean
     */
    public function isVideo()
    {
        return isset(static::$mimeToIcon[$this->getMimeType()]) && static::$mimeToIcon[$this->getMimeType()] == 'video';
    }

    /**
     * Is current document an audio file.
     *
     * @return boolean
     */
    public function isAudio()
    {
        return isset(static::$mimeToIcon[$this->getMimeType()]) && static::$mimeToIcon[$this->getMimeType()] == 'audio';
    }

    /**
     * Is current document a PDF file.
     *
     * @return bool
     */
    public function isPdf()
    {
        return isset(static::$mimeToIcon[$this->getMimeType()]) && static::$mimeToIcon[$this->getMimeType()] == 'pdf';
    }

    /**
     * @ORM\Column(type="string")
     */
    private $folder;

    /**
     * @return string
     */
    public function getFolder()
    {
        return $this->folder;
    }

    /**
     * Set folder name.
     *
     * @param $folder
     * @return $this
     */
    public function setFolder($folder)
    {
        $this->folder = $folder;
        return $this->folder;
    }

    /**
     * @return string
     */
    public function getRelativeUrl()
    {
        if (null !== $this->filename) {
            return $this->getFolder() . '/' . $this->getFilename();
        } else {
            return null;
        }
    }

    /**
     * Return absolute file path according to its
     * privacy status.
     *
     * @return string
     * @deprecated Use Packages::getDocumentFilePath() method instead. Will be removed in Standard Edition.
     */
    public function getAbsolutePath()
    {
        return $this->isPrivate() ? $this->getPrivateAbsolutePath() : $this->getPublicAbsolutePath();
    }

    /**
     * Only return public absolute file path.
     *
     * @return string|null
     * @deprecated Use Assets package service instead. Will be removed in Standard Edition.
     */
    public function getPublicAbsolutePath()
    {
        if (null !== $this->filename) {
            return static::getFilesFolder() . '/' . $this->getRelativeUrl();
        } else {
            return null;
        }
    }

    /**
     * Only return private absolute file path.
     *
     * @return string|null
     * @deprecated Use Assets package service instead. Will be removed in Standard Edition.
     */
    public function getPrivateAbsolutePath()
    {
        if (null !== $this->filename) {
            return static::getPrivateFilesFolder() . '/' . $this->getRelativeUrl();
        } else {
            return null;
        }
    }

    /**
     * @ORM\Column(type="string", name="embedId", unique=false, nullable=true)
     */
    protected $embedId = null;

    /**
     * @return string
     */
    public function getEmbedId()
    {
        return $this->embedId;
    }

    /**
     * @param string $embedId
     * @return $this
     */
    public function setEmbedId($embedId)
    {
        $this->embedId = $embedId;

        return $this;
    }

    /**
     * @ORM\Column(type="string", name="embedPlatform", unique=false, nullable=true)
     */
    protected $embedPlatform = null;

    /**
     * @return string
     */
    public function getEmbedPlatform()
    {
        return $this->embedPlatform;
    }

    /**
     * @param string $embedPlatform
     * @return $this
     */
    public function setEmbedPlatform($embedPlatform)
    {
        $this->embedPlatform = $embedPlatform;

        return $this;
    }

    /**
     * Tells if current document has embed media informations.
     *
     * @return boolean
     */
    public function isEmbed()
    {
        return (null !== $this->embedId && null !== $this->embedPlatform);
    }

    /**
     * @ORM\Column(type="boolean", nullable=false, options={"default" = false})
     */
    private $private = false;

    /**
     * @return boolean
     */
    public function isPrivate()
    {
        return $this->private;
    }

    /**
     * @param boolean $private
     * @return $this
     */
    public function setPrivate($private)
    {
        $this->private = (boolean) $private;

        return $this;
    }

    /**
     * @ORM\OneToMany(targetEntity="RZ\Roadiz\Core\Entities\NodesSourcesDocuments", mappedBy="document")
     * @var ArrayCollection
     */
    protected $nodesSourcesByFields = null;

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getNodesSourcesByFields()
    {
        return $this->nodesSourcesByFields;
    }

    /**
     * @ORM\ManyToMany(targetEntity="RZ\Roadiz\Core\Entities\Folder", mappedBy="documents")
     * @ORM\JoinTable(name="documents_folders")
     */
    protected $folders;

    /**
     * @return ArrayCollection
     */
    public function getFolders()
    {
        return $this->folders;
    }

    /**
     * @param Folder $folder
     * @return $this
     */
    public function addFolder(Folder $folder)
    {
        if (!$this->getFolders()->contains($folder)) {
            $this->folders->add($folder);
        }

        return $this;
    }

    /**
     * @ORM\OneToMany(targetEntity="DocumentTranslation", mappedBy="document", orphanRemoval=true, fetch="EAGER")
     * @var ArrayCollection
     */
    protected $documentTranslations;

    /**
     * @return ArrayCollection
     */
    public function getDocumentTranslations()
    {
        return $this->documentTranslations;
    }

    /**
     * @param Translation $translation
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDocumentTranslationsByTranslation(Translation $translation)
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('translation', $translation));

        return $this->documentTranslations->matching($criteria);
    }

    /**
     * @param DocumentTranslation $documentTranslation
     * @return $this
     */
    public function addDocumentTranslation(DocumentTranslation $documentTranslation)
    {
        if (!$this->getDocumentTranslations()->contains($documentTranslation)) {
            $this->documentTranslations->add($documentTranslation);
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function hasTranslations()
    {
        return (boolean) $this->getDocumentTranslations()->count();
    }

    /**
     * Create a new Document.
     */
    public function __construct()
    {
        $this->folders = new ArrayCollection();
        $this->documentTranslations = new ArrayCollection();
        $this->nodesSourcesByFields = new ArrayCollection();
        $this->folder = substr(hash("crc32b", date('YmdHi')), 0, 12);
    }

    /**
     * @return string Return absolute path to public files.
     * @deprecated Use Kernel::getPublicFilesPath() whenever it’s possible. This will be removed in Standard Edition.
     */
    public static function getFilesFolder()
    {
        return ROADIZ_ROOT . '/' . static::getFilesFolderName();
    }

    /**
     * @return string
     * @deprecated Use Kernel::getPublicFilesBasePath() whenever it’s possible. This will be removed in Standard Edition.
     */
    public static function getFilesFolderName()
    {
        return 'files';
    }

    /**
     * @return string Return absolute path to private files. This path should be protected.
     * @deprecated Use Kernel::getPrivateFilesPath() whenever it’s possible. This will be removed in Standard Edition.
     */
    public static function getPrivateFilesFolder()
    {
        return ROADIZ_ROOT . '/' . static::getPrivateFilesFolderName();
    }

    /**
     * @return string
     * @deprecated Use Kernel::getPrivateFilesBasePath() whenever it’s possible. This will be removed in Standard Edition.
     */
    public static function getPrivateFilesFolderName()
    {
        return 'files/private';
    }

    /**
     * Gets the value of rawDocument.
     *
     * @return Document|null
     */
    public function getRawDocument()
    {
        return $this->rawDocument;
    }

    /**
     * Sets the value of rawDocument.
     *
     * @param Document|null $rawDocument the raw document
     *
     * @return self
     */
    public function setRawDocument(Document $rawDocument = null)
    {
        $this->rawDocument = $rawDocument;

        return $this;
    }

    /**
     * Is document a raw one.
     *
     * @return boolean
     */
    public function isRaw()
    {
        return $this->raw;
    }

    /**
     * Sets the value of raw.
     *
     * @param boolean $raw the raw
     *
     * @return self
     */
    public function setRaw($raw)
    {
        $this->raw = (boolean) $raw;

        return $this;
    }

    /**
     * @return bool
     * @deprecated Use Packages methods to manage documents server paths. This will be removed in Standard Edition.
     */
    public function fileExists()
    {
        $fs = new Filesystem();
        return $fs->exists($this->getAbsolutePath());
    }

    /**
     * Gets the downscaledDocument.
     *
     * @return Document|null
     */
    public function getDownscaledDocument()
    {
        return $this->downscaledDocument;
    }

    /**
     * Get image orientation.
     *
     * - Return null if document is not an Image
     * - Return `'landscape'` if width is higher or equal to height
     * - Return `'portrait'` if height is strictly lower to width
     *
     * @return string|null
     * @deprecated Use Twig filter "imageOrientation" instead. This will be removed in Standard Edition.
     */
    public function getOrientation()
    {
        if ($this->isImage()) {
            $size = $this->getImageSize();
            return $size['width'] >= $size['height'] ? 'landscape' : 'portrait';
        }

        return null;
    }

    /**
     * @return array|null
     * @deprecated Use Twig filter "imageSize" instead. This will be removed in Standard Edition.
     */
    public function getImageSize()
    {
        if ($this->isImage()) {
            $manager = new ImageManager();
            $imageProcess = $manager->make($this->getAbsolutePath());
            return [
                'width' => $imageProcess->width(),
                'height' => $imageProcess->height(),
            ];
        }

        return null;
    }

    /**
     * @return float|null
     * @deprecated Use Twig filter "imageRatio" instead. This will be removed in Standard Edition.
     */
    public function getImageSizeRatio()
    {
        if ($this->isImage()) {
            $size = $this->getImageSize();
            return $size['width']/$size['height'];
        }

        return null;
    }

    /**
     * Clone current document.
     */
    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
            $this->rawDocument = null;
        }
    }
}
