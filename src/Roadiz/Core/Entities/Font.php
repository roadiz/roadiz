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

use Doctrine\ORM\Mapping as ORM;
use RZ\Roadiz\Core\AbstractEntities\AbstractDateTimed;
use RZ\Roadiz\Core\Handlers\FontHandler;
use RZ\Roadiz\Utils\StringHandler;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Fonts are entities which store each webfont file for a
 * font-family and a font-variant.
 *
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\FontRepository")
 * @ORM\Table(name="fonts",uniqueConstraints={
 *     @ORM\UniqueConstraint(columns={"name", "variant"})})
 * @ORM\HasLifecycleCallbacks
 */
class Font extends AbstractDateTimed
{
    const REGULAR = 0;
    const ITALIC = 1;
    const BOLD = 2;
    const BOLD_ITALIC = 3;
    const LIGHT = 4;
    const LIGHT_ITALIC = 5;

    const MIME_DEFAULT = 'application/octet-stream';
    const MIME_SVG = 'image/svg+xml';
    const MIME_TTF = 'application/x-font-truetype';
    const MIME_OTF = 'application/x-font-opentype';
    const MIME_WOFF = 'application/font-woff';
    const MIME_WOFF2 = 'application/font-woff2';
    const MIME_EOT = 'application/vnd.ms-fontobject';

    /**
     * Get readable variant association
     *
     * @var array
     */
    public static $variantToHuman = [
        Font::REGULAR => 'font_variant.regular',
        Font::ITALIC => 'font_variant.italic',
        Font::BOLD => 'font_variant.bold',
        Font::BOLD_ITALIC => 'font_variant.bold.italic',
        Font::LIGHT => 'font_variant.light',
        Font::LIGHT_ITALIC => 'font_variant.light.italic',
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
                    'weight' => 300,
                ];

            case static::LIGHT:
                return [
                    'style' => 'normal',
                    'weight' => 300,
                ];

            case static::BOLD_ITALIC:
                return [
                    'style' => 'italic',
                    'weight' => 'bold',
                ];

            case static::BOLD:
                return [
                    'style' => 'normal',
                    'weight' => 'bold',
                ];

            case static::ITALIC:
                return [
                    'style' => 'italic',
                    'weight' => 'normal',
                ];

            case static::REGULAR:
            default:
                return [
                    'style' => 'normal',
                    'weight' => 'normal',
                ];
        }
    }

    /** @var UploadedFile */
    protected $eotFile;

    /** @var UploadedFile */
    protected $woffFile;

    /** @var UploadedFile */
    protected $woff2File;

    /** @var UploadedFile */
    protected $otfFile;

    /** @var UploadedFile */
    protected $svgFile;

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
    private $hash = "";
    /**
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * @param string $hash
     *
     * @return $this
     */
    public function setHash($hash)
    {
        $this->hash = $hash;

        return $this;
    }

    /**
     * @param string $secret
     *
     * @return $this
     */
    public function generateHashWithSecret($secret)
    {
        $this->hash = substr(hash("crc32b", $this->name . $secret), 0, 12);

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
        return $this->getFolder() . '/' . $this->getEOTFilename();
    }
    /**
     * @return string
     */
    public function getEOTAbsolutePath()
    {
        return static::getFilesFolder() . '/' . $this->getEOTRelativeUrl();
    }
    /**
     * @return string
     */
    public function getWOFFRelativeUrl()
    {
        return $this->getFolder() . '/' . $this->getWOFFFilename();
    }
    /**
     * @return string
     */
    public function getWOFFAbsolutePath()
    {
        return static::getFilesFolder() . '/' . $this->getWOFFRelativeUrl();
    }
    /**
     * @return string
     */
    public function getWOFF2RelativeUrl()
    {
        return $this->getFolder() . '/' . $this->getWOFF2Filename();
    }
    /**
     * @return string
     */
    public function getWOFF2AbsolutePath()
    {
        return static::getFilesFolder() . '/' . $this->getWOFF2RelativeUrl();
    }
    /**
     * @return string
     */
    public function getOTFRelativeUrl()
    {
        return $this->getFolder() . '/' . $this->getOTFFilename();
    }
    /**
     * @return string
     */
    public function getOTFAbsolutePath()
    {
        return static::getFilesFolder() . '/' . $this->getOTFRelativeUrl();
    }
    /**
     * @return string
     */
    public function getSVGRelativeUrl()
    {
        return $this->getFolder() . '/' . $this->getSVGFilename();
    }
    /**
     * @return string
     */
    public function getSVGAbsolutePath()
    {
        return static::getFilesFolder() . '/' . $this->getSVGRelativeUrl();
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
     * @return string Return absolute path to fonts folder. This path should be protected.
     * @deprecated Use Kernel::getFontsFilesPath() whenever it’s possible. This will be removed in Standard Edition.
     */
    public static function getFilesFolder()
    {
        return ROADIZ_ROOT . '/' . static::getFilesFolderName();
    }
    /**
     * @return string
     * @deprecated Use Kernel::getFontsFilesBasePath() whenever it’s possible. This will be removed in Standard Edition.
     */
    public static function getFilesFolderName()
    {
        return 'files/fonts';
    }

    /**
     * @return FontHandler
     */
    public function getHandler()
    {
        return new FontHandler($this);
    }

    /**
     * Gets the value of eotFile.
     *
     * @return File
     */
    public function getEotFile()
    {
        return $this->eotFile;
    }

    /**
     * Sets the value of eotFile.
     *
     * @param File $eotFile the eot file
     *
     * @return self
     */
    public function setEotFile(File $eotFile)
    {
        $this->eotFile = $eotFile;

        return $this;
    }

    /**
     * Gets the value of woffFile.
     *
     * @return File
     */
    public function getWoffFile()
    {
        return $this->woffFile;
    }

    /**
     * Sets the value of woffFile.
     *
     * @param File $woffFile the woff file
     *
     * @return self
     */
    public function setWoffFile(File $woffFile)
    {
        $this->woffFile = $woffFile;

        return $this;
    }

    /**
     * Gets the value of woff2File.
     *
     * @return File
     */
    public function getWoff2File()
    {
        return $this->woff2File;
    }

    /**
     * Sets the value of woff2File.
     *
     * @param File $woff2File the woff2 file
     *
     * @return self
     */
    public function setWoff2File(File $woff2File)
    {
        $this->woff2File = $woff2File;

        return $this;
    }

    /**
     * Gets the value of otfFile.
     *
     * @return File
     */
    public function getOtfFile()
    {
        return $this->otfFile;
    }

    /**
     * Sets the value of otfFile.
     *
     * @param File $otfFile the otf file
     *
     * @return self
     */
    public function setOtfFile(File $otfFile)
    {
        $this->otfFile = $otfFile;

        return $this;
    }

    /**
     * Gets the value of svgFile.
     *
     * @return File
     */
    public function getSvgFile()
    {
        return $this->svgFile;
    }

    /**
     * Sets the value of svgFile.
     *
     * @param File $svgFile the svg file
     *
     * @return self
     */
    public function setSvgFile(File $svgFile)
    {
        $this->svgFile = $svgFile;

        return $this;
    }

    /**
     * Called before saving the entity
     *
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function preUpload()
    {
        if ($this->hash == "") {
            $this->generateHashWithSecret('default_roadiz_secret');
        }

        if (null !== $this->svgFile) {
            $this->setSVGFilename($this->svgFile->getClientOriginalName());
        }
        if (null !== $this->otfFile) {
            $this->setOTFFilename($this->otfFile->getClientOriginalName());
        }
        if (null !== $this->eotFile) {
            $this->setEOTFilename($this->eotFile->getClientOriginalName());
        }
        if (null !== $this->woffFile) {
            $this->setWOFFFilename($this->woffFile->getClientOriginalName());
        }
        if (null !== $this->woff2File) {
            $this->setWOFF2Filename($this->woff2File->getClientOriginalName());
        }
    }

    /**
     * Called after entity persistence
     *
     * @ORM\PostPersist()
     * @ORM\PostUpdate()
     */
    public function upload()
    {
        if (null !== $this->svgFile) {
            $this->svgFile->move(static::getFilesFolder() . '/' . $this->getFolder(), $this->getSVGFilename());
            $this->svgFile = null;
        }
        if (null !== $this->otfFile) {
            $this->otfFile->move(static::getFilesFolder() . '/' . $this->getFolder(), $this->getOTFFilename());
            $this->otfFile = null;
        }
        if (null !== $this->eotFile) {
            $this->eotFile->move(static::getFilesFolder() . '/' . $this->getFolder(), $this->getEOTFilename());
            $this->eotFile = null;
        }
        if (null !== $this->woffFile) {
            $this->woffFile->move(static::getFilesFolder() . '/' . $this->getFolder(), $this->getWOFFFilename());
            $this->woffFile = null;
        }
        if (null !== $this->woff2File) {
            $this->woff2File->move(static::getFilesFolder() . '/' . $this->getFolder(), $this->getWOFF2Filename());
            $this->woff2File = null;
        }
    }

    /**
     * Called before entity removal
     *
     * @ORM\PreRemove()
     */
    public function removeUpload()
    {
        $fs = new Filesystem();
        try {
            if (null !== $this->svgFilename) {
                $fs->remove($this->getSVGAbsolutePath());
            }
            if (null !== $this->otfFilename) {
                $fs->remove($this->getOTFAbsolutePath());
            }
            if (null !== $this->eotFilename) {
                $fs->remove($this->getEOTAbsolutePath());
            }
            if (null !== $this->woffFilename) {
                $fs->remove($this->getWOFFAbsolutePath());
            }
            if (null !== $this->woff2Filename) {
                $fs->remove($this->getWOFF2AbsolutePath());
            }
        } catch (IOExceptionInterface $e) {
            //do nothing
        }
    }
}
