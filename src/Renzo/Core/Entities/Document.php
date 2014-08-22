<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file Document.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\AbstractEntities\AbstractDateTimed;
use RZ\Renzo\Core\Utils\StringHandler;
use RZ\Renzo\Core\Viewers\DocumentViewer;
use RZ\Renzo\Core\Handlers\DocumentHandler;
/**
 * Documents entity represent a file on server with datetime and naming.
 *
 * @Entity(repositoryClass="RZ\Renzo\Core\Entities\DocumentRepository")
 * @Table(name="documents")
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
    public static $mimeToIcon = array(
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
        'video/x-flv'=> 'video',
        'application/gzip' => 'archive',
        'application/zip' => 'archive',
        'application/x-bzip2' => 'archive',
        'application/x-tar' => 'archive',
        'application/x-7z-compressed' => 'archive',
        'application/x-apple-diskimage' => 'archive',
        'application/x-rar-compressed' => 'archive',
        'application/msword' => 'word',
        'application/vnd.ms-excel' => 'excel',
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
    );

    /**
     * @Column(type="string", nullable=false)
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
     * @Column(name="mime_type", type="string", nullable=true)
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
     * Get short type name for current document Mime type.
     *
     * @return string
     */
    public function getShortType()
    {
        return static::$mimeToIcon[$this->getMimeType()];
    }

    /**
     * Is current document an image.
     *
     * @return boolean
     */
    public function isImage()
    {
        return static::$mimeToIcon[$this->getMimeType()] == 'image';
    }
    /**
     * Is current document a video.
     *
     * @return boolean
     */
    public function isVideo()
    {
        return static::$mimeToIcon[$this->getMimeType()] == 'video';
    }
    /**
     * Is current document an audio file.
     *
     * @return boolean
     */
    public function isAudio()
    {
        return static::$mimeToIcon[$this->getMimeType()] == 'audio';
    }

    /**
     * @Column(type="string", nullable=true)
     */
    private $name;
    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @Column(type="string")
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
     * @return string
     */
    public function getRelativeUrl()
    {
        return $this->getFolder().'/'.$this->getFilename();
    }

    /**
     * @return string
     */
    public function getAbsolutePath()
    {
        return static::getFilesFolder().'/'.$this->getRelativeUrl();
    }

    /**
     * @Column(type="text", nullable=true)
     */
    private $description;
    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }
    /**
     * @param string $description
     *
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @Column(type="text", nullable=true)
     */
    private $copyright;
    /**
     * @return string
     */
    public function getCopyright()
    {
        return $this->copyright;
    }
    /**
     * @param string $copyright
     *
     * @return $this
     */
    public function setCopyright($copyright)
    {
        $this->copyright = $copyright;

        return $this;
    }

    /**
     * @Column(type="boolean")
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
     * @ManyToMany(targetEntity="Tag", inversedBy="documents")
     * @JoinTable(name="documents_tags")
     * @var ArrayCollection
     */
    private $tags = null;
    /**
     * @return ArrayCollection
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @return RZ\Renzo\Core\Viewers\DocumentViewer
     */
    public function getViewer()
    {
        return new DocumentViewer($this);
    }
    /**
     * @return RZ\Renzo\Core\Handlers\DocumentHandler
     */
    public function getHandler()
    {
        return new DocumentHandler($this);
    }

    /**
     * @OneToMany(targetEntity="RZ\Renzo\Core\Entities\NodesSourcesDocuments", mappedBy="document")
     * @var ArrayCollection
     */
    protected $nodesSourcesByFields = null;

    /**
     * Create a new Document.
     */
    public function __construct()
    {
        parent::__construct();

        $this->tags = new ArrayCollection();
        $this->nodesSourcesByFields = new ArrayCollection();
        $this->folder = substr(hash("crc32b", date('YmdHi')), 0, 12);
    }

    /**
     * @return string
     */
    public static function getFilesFolder()
    {
        return RENZO_ROOT.'/'.static::getFilesFolderName();
    }
    /**
     * @return string
     */
    public static function getFilesFolderName()
    {
        return 'files';
    }
}