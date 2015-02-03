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
 * @file Font.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Entities;

use RZ\Roadiz\Core\Utils\StringHandler;
use RZ\Roadiz\Core\AbstractEntities\AbstractDateTimed;
use Doctrine\ORM\Mapping as ORM;

/**
 * Fonts are entities which store each webfont file for a
 * font-family and a font-variant.
 *
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\FontRepository")
 * @ORM\Table(name="fonts",uniqueConstraints={
 *     @ORM\UniqueConstraint(name="name_variant_idx", columns={"name", "variant"})})
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
    public static $extensionToMime = [
        'svg'   => 'image/svg+xml',
        'ttf'   => 'application/x-font-truetype',
        'otf'   => 'application/x-font-opentype',
        'woff'  => 'application/font-woff',
        'woff2' => 'application/font-woff2',
        'eot'   => 'application/vnd.ms-fontobject',
    ];

    /**
     * Get readable variant association
     *
     * @var array
     */
    protected static $variantToHuman = [
        Font::REGULAR      => 'Regular',
        Font::ITALIC       => 'Italic',
        Font::BOLD         => 'Bold',
        Font::BOLD_ITALIC  => 'Bold italic',
        Font::LIGHT        => 'Light',
        Font::LIGHT_ITALIC => 'Light italic',
    ];

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
     * @ORM\Column(type="integer", name="variant", unique=false, nullable=false)
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
                return [
                    'style' => 'italic',
                    'weight' => 300
                ];
            case static::LIGHT:
                return [
                    'style' => 'normal',
                    'weight' => 300
                ];

            case static::BOLD_ITALIC:
                return [
                    'style' => 'italic',
                    'weight' => 'bold'
                ];

            case static::BOLD:
                return [
                    'style' => 'normal',
                    'weight' => 'bold'
                ];

            case static::ITALIC:
                return [
                    'style' => 'italic',
                    'weight' => 'normal'
                ];

            case static::REGULAR:
            default:
                return [
                    'style' => 'normal',
                    'weight' => 'normal'
                ];
        }
    }


    /**
     * @ORM\Column(type="string", nullable=true, name="eot_filename")
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
     * @ORM\Column(type="string", nullable=true, name="woff_filename")
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
     * @ORM\Column(type="string", nullable=true, name="woff2_filename")
     */
    private $woff2Filename;
    /**
     * @return string
     */
    public function getWOFF2Filename()
    {
        return $this->woff2Filename;
    }
    /**
     * @param string $woff2Filename
     *
     * @return $this
     */
    public function setWOFF2Filename($woff2Filename)
    {
        $this->woff2Filename = StringHandler::cleanForFilename($woff2Filename);

        return $this;
    }

    /**
     * @ORM\Column(type="string", nullable=true, name="otf_filename")
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
     * @ORM\Column(type="string", nullable=true, name="svg_filename")
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
     * @ORM\Column(type="string", nullable=false, unique=false)
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
     * @ORM\Column(type="string", nullable=false, unique=false)
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
    public function getWOFF2RelativeUrl()
    {
        return $this->getFolder().'/'.$this->getWOFF2Filename();
    }
    /**
     * @return string
     */
    public function getWOFF2AbsolutePath()
    {
        return static::getFilesFolder().'/'.$this->getWOFF2RelativeUrl();
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
     * @ORM\Column(type="text", nullable=true)
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
        return ROADIZ_ROOT.'/'.static::getFilesFolderName();
    }
    /**
     * @return string
     */
    public static function getFilesFolderName()
    {
        return 'files/fonts';
    }

    /**
     * @return RZ\Roadiz\Core\Handlers\FontHandler
     */
    public function getHandler()
    {
        return new \RZ\Roadiz\Core\Handlers\FontHandler($this);
    }
    /**
     * @return RZ\Roadiz\Core\Handlers\FontViewer
     */
    public function getViewer()
    {
        return new \RZ\Roadiz\Core\Viewers\FontViewer($this);
    }
}
