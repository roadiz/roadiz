<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file Font.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\Entities\Document;
use RZ\Renzo\Core\Utils\StringHandler;
use RZ\Renzo\Core\AbstractEntities\AbstractDateTimed;

/**
 * Fonts are entities which store each webfont file for a
 * font-family and a font-variant.
 *
 * @Entity(repositoryClass="RZ\Renzo\Core\Utils\EntityRepository")
 * @Table(name="fonts",uniqueConstraints={
 *     @UniqueConstraint(name="name_variant_idx", columns={"name", "variant"})})
 */
class Font extends AbstractDateTimed
{
    const REGULAR      = 0;
    const ITALIC       = 1;
    const BOLD         = 2;
    const BOLD_ITALIC  = 3;
    const LIGHT        = 4;
    const LIGHT_ITALIC = 5;

    /**
     * Get Mime type for each font file extensions.
     *
     * @var array
     */
    public static $extensionToMime = array(
        'svg' => 'image/svg+xml',
        'ttf' => 'application/x-font-truetype',
        'otf' => 'application/x-font-opentype',
        'woff' => 'application/font-woff',
        'eot' => 'application/vnd.ms-fontobject',
    );

    /**
     * Get readable variant association
     *
     * @var array
     */
    protected static $variantToHuman = array(
        Font::REGULAR => 'Regular',
        Font::ITALIC => 'Italic',
        Font::BOLD => 'Bold',
        Font::BOLD_ITALIC => 'Bold italic',
        Font::LIGHT => 'Light',
        Font::LIGHT_ITALIC => 'Light italic',
    );

    /**
     * Get a readable string to describe current font variant.
     *
     * @return string
     */
    public function getReadableVariant()
    {
        return static::$variantToHuman[$this->getVariant()];
    }

    /**
     * @Column(type="integer", name="variant", unique=false, nullable=false)
     */
    protected $variant = Font::REGULAR;
    /**
     * @return integer
     */
    public function getVariant()
    {
        return $this->variant;
    }
    /**
     * @param integer $variant
     *
     * @return $this
     */
    public function setVariant($variant)
    {
        $this->variant = $variant;

        return $this;
    }

    /**
     * Return font variant information for CSS font-face
     * into a simple array.
     *
     * * style
     * * weight
     *
     * @return array
     */
    public function getFontVariantInfos()
    {
        switch ($this->getVariant()) {
            case static::LIGHT_ITALIC:
                return array(
                    'style' => 'italic',
                    'weight' => 'lighter'
                );
            case static::LIGHT:
                return array(
                    'style' => 'normal',
                    'weight' => 'lighter'
                );

            case static::BOLD_ITALIC:
                return array(
                    'style' => 'italic',
                    'weight' => 'bold'
                );

            case static::BOLD:
                return array(
                    'style' => 'normal',
                    'weight' => 'bold'
                );

            case static::ITALIC:
                return array(
                    'style' => 'italic',
                    'weight' => 'normal'
                );

            case static::REGULAR:
            default:
                return array(
                    'style' => 'normal',
                    'weight' => 'normal'
                );
        }
    }


    /**
     * @Column(type="string", nullable=true, name="eot_filename")
     */
    private $eotFilename;
    /**
     * @return string
     */
    public function getEOTFilename()
    {
        return $this->eotFilename;
    }
    /**
     * @param string $eotFilename
     *
     * @return $this
     */
    public function setEOTFilename($eotFilename)
    {
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
    public function getWOFFFilename()
    {
        return $this->woffFilename;
    }
    /**
     * @param string $woffFilename
     *
     * @return $this
     */
    public function setWOFFFilename($woffFilename)
    {
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
    public function getOTFFilename()
    {
        return $this->otfFilename;
    }
    /**
     * @param string $otfFilename
     *
     * @return $this
     */
    public function setOTFFilename($otfFilename)
    {
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
    public function getSVGFilename()
    {
        return $this->svgFilename;
    }
    /**
     * @param string $svgFilename
     *
     * @return $this
     */
    public function setSVGFilename($svgFilename)
    {
        $this->svgFilename = StringHandler::cleanForFilename($svgFilename);

        return $this;
    }


    /**
     * @Column(type="string", nullable=false, unique=false)
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
     * @Column(type="string", nullable=false, unique=false)
     */
    private $hash;
    /**
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }
    /**
     * @param string $secret
     *
     * @return $this
     */
    public function setHash($secret)
    {
        $this->hash = substr(hash("crc32b", $this->name.$secret), 0, 12);

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
    public function getEOTRelativeUrl()
    {
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
    public function getWOFFRelativeUrl()
    {
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
    public function getOTFRelativeUrl()
    {
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
    public function getSVGRelativeUrl()
    {
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
     * Create a new Font and generate a random folder name.
     */
    public function __construct()
    {
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
        return 'files/fonts';
    }

    /**
     * @return RZ\Renzo\Core\Handlers\FontHandler
     */
    public function getHandler()
    {
        return new \RZ\Renzo\Core\Handlers\FontHandler($this);
    }
    /**
     * @return RZ\Renzo\Core\Handlers\FontViewer
     */
    public function getViewer()
    {
        return new \RZ\Renzo\Core\Viewers\FontViewer($this);
    }
}
