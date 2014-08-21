<?php 

namespace RZ\Renzo\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\Entities\Document;
use RZ\Renzo\Core\Utils\StringHandler;
use RZ\Renzo\Core\AbstractEntities\DateTimed;

/**
 * @Entity(repositoryClass="RZ\Renzo\Core\Utils\EntityRepository")
 * @Table(name="fonts",uniqueConstraints={
 *     @UniqueConstraint(name="name_variant_idx", columns={"name", "variant"})})
 */
class Font extends DateTimed { 

	const REGULAR      = 0;
	const ITALIC       = 1;
	const BOLD         = 2;
	const BOLD_ITALIC  = 3;
	const LIGHT        = 4;
	const LIGHT_ITALIC = 5;

	/**
	 * @Column(type="integer", name="variant", unique=false, nullable=false)
	 */
	protected $variant = Font::REGULAR;
	/**
	 * @return integer
	 */
	public function getVariant() {
	    return $this->variant;
	}
	/**
	 * @param integer $newvariant
	 */
	public function setVariant($variant) {
	    $this->variant = $variant;
	
	    return $this;
	}
	

	/**
	 * @Column(type="string", nullable=true, name="eot_filename")
	 */
	private $eotFilename;
	/**
	 * @return string
	 */
	public function getEOTFilename() {
	    return $this->eotFilename;
	}
	/**
	 * @param $eotFilename
	 */
	public function setEOTFilename($eotFilename) {

	    $this->eotFilename = StringHandler::cleanForFilename($eotFilename);
	    return $this;
	}

	/**
	 * @Column(type="string", nullable=true, name="woff_filename")
	 */
	private $woffFilename;
	/**
	 * @return string
	 */
	public function getWOFFFilename() {
	    return $this->woffFilename;
	}
	/**
	 * @param $woffFilename
	 */
	public function setWOFFFilename($woffFilename) {

	    $this->woffFilename = StringHandler::cleanForFilename($woffFilename);
	    return $this;
	}

	/**
	 * @Column(type="string", nullable=true, name="otf_filename")
	 */
	private $otfFilename;
	/**
	 * @return string
	 */
	public function getOTFFilename() {
	    return $this->otfFilename;
	}
	/**
	 * @param $otfFilename
	 */
	public function setOTFFilename($otfFilename) {

	    $this->otfFilename = StringHandler::cleanForFilename($otfFilename);
	    return $this;
	}

	/**
	 * @Column(type="string", nullable=true, name="svg_filename")
	 */
	private $svgFilename;
	/**
	 * @return string
	 */
	public function getSVGFilename() {
	    return $this->svgFilename;
	}
	/**
	 * @param $svgFilename
	 */
	public function setSVGFilename($svgFilename) {

	    $this->svgFilename = StringHandler::cleanForFilename($svgFilename);
	    return $this;
	}


	/**
	 * @Column(type="string", nullable=false, unique=false)
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
	 * @Column(type="string", nullable=false, unique=false)
	 */
	private $hash;
	/**
	 * @return
	 */
	public function getHash() {
	    return $this->hash;
	}
	/**
	 * @param $newhash 
	 * @param $secret 
	 */
	public function setHash( $secret ) {
	    $this->hash = substr(hash("crc32b",$this->name.$secret), 0, 12);
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
	public function getEOTRelativeUrl() {
	    return $this->getFolder().'/'.$this->getEOTFilename();
	}
	/**
	 * @return string
	 */
	public function getEOTAbsolutePath()
	{
		return static::getFilesFolder().'/'.$this->getEOTRelativeUrl();
	}
	/**
	 * @return string
	 */
	public function getWOFFRelativeUrl() {
	    return $this->getFolder().'/'.$this->getWOFFFilename();
	}
	/**
	 * @return string
	 */
	public function getWOFFAbsolutePath()
	{
		return static::getFilesFolder().'/'.$this->getWOFFRelativeUrl();
	}
	/**
	 * @return string
	 */
	public function getOTFRelativeUrl() {
	    return $this->getFolder().'/'.$this->getOTFFilename();
	}
	/**
	 * @return string
	 */
	public function getOTFAbsolutePath()
	{
		return static::getFilesFolder().'/'.$this->getOTFRelativeUrl();
	}
	/**
	 * @return string
	 */
	public function getSVGRelativeUrl() {
	    return $this->getFolder().'/'.$this->getSVGFilename();
	}
	/**
	 * @return string
	 */
	public function getSVGAbsolutePath()
	{
		return static::getFilesFolder().'/'.$this->getSVGRelativeUrl();
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
     * 
     */
	public function __construct()
    {
    	parent::__construct();
        $this->folder = substr(hash("crc32b", date('YmdHi')), 0, 12);
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
    	return 'files/fonts';
    }
}