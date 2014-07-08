<?php 

namespace RZ\Renzo\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\AbstractEntities\DateTimed;
use RZ\Renzo\Core\Utils\StringHandler;
use RZ\Renzo\Core\Viewers\DocumentViewer;

/**
 * @Entity
 * @Table(name="documents")
 */
class Document extends DateTimed
{

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
		'image/svg+xml' => 'image',
		'application/pdf' => 'pdf',
		'application/ogg' => 'video',
		'video/ogg' => 'video',
		'audio/mpeg' => 'audio',
		'audio/x-wav' => 'audio',
		'audio/wav' => 'audio',
		'video/mpeg' => 'video',
		'video/mp4' => 'video',
		'video/quicktime' => 'video',
		'video/x-flv'=> 'video',
		'application/gzip' => 'archive',
		'application/zip' => 'zip',
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
	);

	/**
	 * @Column(type="string", nullable=false)
	 */
	private $filename;
	/**
	 * @return string
	 */
	public function getFilename() {
	    return $this->filename;
	}
	/**
	 * @param $filename
	 */
	public function setFilename($filename) {

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
	public function getMimeType() {
	    return $this->mimeType;
	}
	/**
	 * @param string $newmimeType
	 */
	public function setMimeType($mimeType) {
	    $this->mimeType = $mimeType;
	
	    return $this;
	}

	/**
	 * Get short type name for current document Mime type
	 * @return string
	 */
	public function getShortType()
	{
		return static::$mimeToIcon[$this->getMimeType()];
	}

	/**
	 * Is current document an image
	 * 
	 * @return boolean
	 */
	public function isImage()
	{
		return static::$mimeToIcon[$this->getMimeType()] == 'image';
	}


	/**
	 * @Column(type="string", nullable=true)
	 */
	private $name;
	/**
	 * @return
	 */
	public function getName() {
	    return $this->name;
	}
	/**
	 * @param $newnodeName 
	 */
	public function setName($name) {
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
	public function getFolder() {
	    return $this->folder;
	}

	/**
	 * @return string
	 */
	public function getRelativeUrl() {
	    return $this->getFolder().'/'.$this->getFilename();
	}

	/**
	 * 
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
	public function getDescription() {
	    return $this->description;
	}
	public function setDescription($description) {
	    $this->description = $description;
	
	    return $this;
	}
	
	/**
	 * @Column(type="boolean")
	 */
	private $private = true;
	/**
	 * @return [type] [description]
	 */
	public function isPrivate() {
	    return $this->private;
	}
	/**
	 * @param [type] $newvisible [description]
	 */
	public function setPrivate($private) {
	    $this->private = (boolean)$private;
	
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
    public function getTags() {
        return $this->tags;
    }

    /**
     * @return RZ\Renzo\Core\Viewers\DocumentViewer
     */
    public function getViewer()
    {
    	return new DocumentViewer( $this );
    }
	
    /**
     * 
     */
	public function __construct()
    {
    	parent::__construct();
    	
        $this->tags = new ArrayCollection();
        $this->folder = substr(hash("crc32b", date('YmdHis')), 0, 12);
    }

    /**
     * 
     * @return string
     */
    public static function getFilesFolder()
    {
    	return RENZO_ROOT.'/'.static::getFilesFolderName();
    }
    public static function getFilesFolderName()
    {
    	return 'files';
    }
}