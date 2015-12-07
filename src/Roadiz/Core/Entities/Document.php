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
use Doctrine\ORM\Mapping as ORM;
use Intervention\Image\ImageManager;
use RZ\Roadiz\Core\AbstractEntities\AbstractDateTimed;
use RZ\Roadiz\Core\Handlers\DocumentHandler;
use RZ\Roadiz\Core\Viewers\DocumentViewer;
use RZ\Roadiz\Utils\StringHandler;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * Documents entity represent a file on server with datetime and naming.
 *
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\DocumentRepository")
 * @ORM\HasLifecycleCallbacks()
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
        'application/ogg' => 'video',
        'video/ogg' => 'video',
        'video/webm' => 'video',
        'audio/mpeg' => 'audio',
        'audio/x-wav' => 'audio',
        'audio/wav' => 'audio',
        'video/mpeg' => 'video',
        'video/mp4' => 'video',
        'video/quicktime' => 'video',
        'video/x-flv' => 'video',
        'application/epub+zip' => 'epub',
        'application/gzip' => 'archive',
        'application/zip' => 'archive',
        'application/x-bzip2' => 'archive',
        'application/x-tar' => 'archive',
        'application/x-7z-compressed' => 'archive',
        'application/x-apple-diskimage' => 'archive',
        'application/x-rar-compressed' => 'archive',
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
     * @param string $forlder
     *
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
     */
    public function getAbsolutePath()
    {
        return $this->isPrivate() ? $this->getPrivateAbsolutePath() : $this->getPublicAbsolutePath();
    }
    /**
     * Only return public absolute file path.
     *
     * @return string
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
     * @return string
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
     *
     * @return $this
     */
    public function setPrivate($private)
    {
        $this->private = (boolean) $private;

        return $this;
    }

    /**
     * @return RZ\Roadiz\Core\Viewers\DocumentViewer
     */
    public function getViewer()
    {
        return new DocumentViewer($this);
    }
    /**
     * @return RZ\Roadiz\Core\Handlers\DocumentHandler
     */
    public function getHandler()
    {
        return new DocumentHandler($this);
    }

    /**
     * @ORM\OneToMany(targetEntity="RZ\Roadiz\Core\Entities\NodesSourcesDocuments", mappedBy="document")
     * @var ArrayCollection
     */
    protected $nodesSourcesByFields = null;

    /**
     * @return Doctrine\Common\Collections\ArrayCollection
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
     * @param Document $folder
     */
    public function addFolder(Folder $folder)
    {
        if (!$this->getFolders()->contains($folder)) {
            $this->folders->add($folder);
        }

        return $this;
    }

    /**
     * @ORM\OneToMany(targetEntity="DocumentTranslation", mappedBy="document", orphanRemoval=true)
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
     * @param DocumentTranslation $documentTranslation
     */
    public function addDocumentTranslation(DocumentTranslation $documentTranslation)
    {
        if (!$this->getDocumentTranslations()->contains($documentTranslation)) {
            $this->documentTranslations->add($documentTranslation);
        }

        return $this;
    }

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
     * @return string
     */
    public static function getFilesFolder()
    {
        return ROADIZ_ROOT . '/' . static::getFilesFolderName();
    }
    /**
     * @return string
     */
    public static function getFilesFolderName()
    {
        return 'files';
    }
    /**
     * @return string
     */
    public static function getPrivateFilesFolder()
    {
        return ROADIZ_ROOT . '/' . static::getPrivateFilesFolderName();
    }
    /**
     * @return string
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
     * Unlink file after document has been deleted.
     *
     * @ORM\PostRemove
     */
    public function postRemove()
    {
        $fs = new Filesystem();

        $this->setRawDocument(null);

        if ($fs->exists($this->getAbsolutePath())) {
            $fs->remove($this->getAbsolutePath());
        }
        $this->cleanFileDirectory();
    }

    /**
     * Remove document directory if there is no other file in it.
     *
     * @return boolean
     */
    protected function cleanFileDirectory()
    {
        $dir = dirname($this->getAbsolutePath());
        $fs = new Filesystem();

        if ($fs->exists($dir)) {
            $finder = new Finder();
            $finder->files()->in($dir);

            if (count($finder) <= 0) {
                /*
                 * Directory is empty
                 */
                $fs->remove($dir);
            }
        }

        return false;
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
     */
    public function getOrientation()
    {
        if ($this->isImage()) {
            $manager = new ImageManager();
            $imageProcess = $manager->make($this->getAbsolutePath());
            return $imageProcess->width() >= $imageProcess->height() ? 'landscape' : 'portrait';
        }

        return null;
    }
}
