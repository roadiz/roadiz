<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Entities;

use Doctrine\ORM\Mapping as ORM;
use RZ\Roadiz\Core\AbstractEntities\AbstractDateTimed;
use RZ\Roadiz\Utils\StringHandler;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Fonts are entities which store each webfont file for a
 * font-family and a font-variant.
 *
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\FontRepository")
 * @ORM\Table(name="fonts",uniqueConstraints={
 *     @ORM\UniqueConstraint(columns={"name", "variant"})})
 */
class Font extends AbstractDateTimed
{
    const REGULAR = 0;
    const ITALIC = 1;
    const BOLD = 2;
    const BOLD_ITALIC = 3;
    const LIGHT = 4;
    const LIGHT_ITALIC = 5;
    const MEDIUM = 6;
    const MEDIUM_ITALIC = 7;
    const BLACK = 8;
    const BLACK_ITALIC = 9;
    const THIN = 10;
    const THIN_ITALIC = 11;
    const EXTRA_LIGHT = 12;
    const EXTRA_LIGHT_ITALIC = 13;
    const SEMI_BOLD = 14;
    const SEMI_BOLD_ITALIC = 15;
    const EXTRA_BOLD = 16;
    const EXTRA_BOLD_ITALIC = 17;

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
        Font::THIN => 'font_variant.thin',                      // 100
        Font::THIN_ITALIC => 'font_variant.thin.italic',        // 100
        Font::EXTRA_LIGHT => 'font_variant.extra_light',               // 200
        Font::EXTRA_LIGHT_ITALIC => 'font_variant.extra_light.italic', // 200
        Font::LIGHT => 'font_variant.light',                    // 300
        Font::LIGHT_ITALIC => 'font_variant.light.italic',      // 300
        Font::REGULAR => 'font_variant.regular',                    // 400
        Font::ITALIC => 'font_variant.italic',                      // 400
        Font::MEDIUM => 'font_variant.medium',                  // 500
        Font::MEDIUM_ITALIC => 'font_variant.medium.italic',    // 500
        Font::SEMI_BOLD => 'font_variant.semi_bold',                 // 600
        Font::SEMI_BOLD_ITALIC => 'font_variant.semi_bold.italic',   // 600
        Font::BOLD => 'font_variant.bold',                      // 700
        Font::BOLD_ITALIC => 'font_variant.bold.italic',        // 700
        Font::EXTRA_BOLD => 'font_variant.extra_bold',                // 800
        Font::EXTRA_BOLD_ITALIC => 'font_variant.extra_bold.italic',  // 800
        Font::BLACK => 'font_variant.black',                    // 900
        Font::BLACK_ITALIC => 'font_variant.black.italic',      // 900
    ];
    /**
     * @ORM\Column(type="integer", name="variant", unique=false, nullable=false)
     * @var int
     */
    protected $variant = Font::REGULAR;
    /**
     * @var UploadedFile|null
     */
    protected $eotFile = null;
    /**
     * @var UploadedFile|null
     */
    protected $woffFile = null;
    /**
     * @var UploadedFile|null
     */
    protected $woff2File = null;
    /**
     * @var UploadedFile|null
     */
    protected $otfFile = null;
    /**
     * @var UploadedFile|null
     */
    protected $svgFile = null;
    /**
     * @ORM\Column(type="string", nullable=true, name="eot_filename")
     * @var string|null
     */
    private $eotFilename;
    /**
     * @ORM\Column(type="string", nullable=true, name="woff_filename")
     * @var string|null
     */
    private $woffFilename;
    /**
     * @ORM\Column(type="string", nullable=true, name="woff2_filename")
     * @var string|null
     */
    private $woff2Filename;
    /**
     * @ORM\Column(type="string", nullable=true, name="otf_filename")
     * @var string|null
     */
    private $otfFilename;
    /**
     * @ORM\Column(type="string", nullable=true, name="svg_filename")
     * @var string|null
     */
    private $svgFilename;
    /**
     * @ORM\Column(type="string", nullable=false, unique=false)
     * @var string
     */
    private $name = '';
    /**
     * @ORM\Column(type="string", nullable=false, unique=false)
     * @var string
     */
    private $hash = '';
    /**
     * @ORM\Column(type="string", nullable=false)
     * @var string
     */
    private $folder;
    /**
     * @ORM\Column(type="text", nullable=true)
     * @var string|null
     */
    private $description;

    /**
     * Create a new Font and generate a random folder name.
     */
    public function __construct()
    {
        $this->folder = substr(hash("crc32b", date('YmdHi')), 0, 12);
        $this->initAbstractDateTimed();
    }

    /**
     * Get a readable string to describe current font variant.
     *
     * @return string
     */
    public function getReadableVariant(): string
    {
        return static::$variantToHuman[$this->getVariant()];
    }

    /**
     * @return int
     */
    public function getVariant(): int
    {
        return $this->variant;
    }

    /**
     * @param int $variant
     *
     * @return $this
     */
    public function setVariant(int $variant): Font
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
     * @see https://developer.mozilla.org/fr/docs/Web/CSS/font-weight
     * @return array
     */
    public function getFontVariantInfos(): array
    {
        switch ($this->getVariant()) {
            case static::SEMI_BOLD_ITALIC:
                return [
                    'style' => 'italic',
                    'weight' => 600,
                ];

            case static::SEMI_BOLD:
                return [
                    'style' => 'normal',
                    'weight' => 600,
                ];

            case static::EXTRA_BOLD_ITALIC:
                return [
                    'style' => 'italic',
                    'weight' => 800,
                ];

            case static::EXTRA_BOLD:
                return [
                    'style' => 'normal',
                    'weight' => 800,
                ];

            case static::EXTRA_LIGHT_ITALIC:
                return [
                    'style' => 'italic',
                    'weight' => 200,
                ];

            case static::EXTRA_LIGHT:
                return [
                    'style' => 'normal',
                    'weight' => 200,
                ];

            case static::THIN_ITALIC:
                return [
                    'style' => 'italic',
                    'weight' => 100,
                ];

            case static::THIN:
                return [
                    'style' => 'normal',
                    'weight' => 100,
                ];

            case static::BLACK_ITALIC:
                return [
                    'style' => 'italic',
                    'weight' => 900,
                ];

            case static::BLACK:
                return [
                    'style' => 'normal',
                    'weight' => 900,
                ];

            case static::MEDIUM_ITALIC:
                return [
                    'style' => 'italic',
                    'weight' => 500,
                ];

            case static::MEDIUM:
                return [
                    'style' => 'normal',
                    'weight' => 500,
                ];

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

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName(string $name): Font
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * @param string $hash
     *
     * @return $this
     */
    public function setHash(string $hash): Font
    {
        $this->hash = $hash;

        return $this;
    }

    /**
     * @param string $secret
     * @return $this
     */
    public function generateHashWithSecret(string $secret): Font
    {
        $this->hash = substr(hash("crc32b", $this->name . $secret), 0, 12);

        return $this;
    }

    /**
     * @return string
     */
    public function getEOTRelativeUrl(): ?string
    {
        return $this->getFolder() . '/' . $this->getEOTFilename();
    }

    /**
     * @return string
     */
    public function getFolder(): string
    {
        return $this->folder;
    }

    /**
     * @return string
     */
    public function getEOTFilename(): ?string
    {
        return $this->eotFilename;
    }

    /**
     * @param string $eotFilename
     * @return $this
     */
    public function setEOTFilename(?string $eotFilename): Font
    {
        $this->eotFilename = StringHandler::cleanForFilename($eotFilename);
        return $this;
    }

    /**
     * @return string
     */
    public function getWOFFRelativeUrl(): ?string
    {
        return $this->getFolder() . '/' . $this->getWOFFFilename();
    }

    /**
     * @return string
     */
    public function getWOFFFilename(): ?string
    {
        return $this->woffFilename;
    }

    /**
     * @param string $woffFilename
     * @return $this
     */
    public function setWOFFFilename(?string $woffFilename): Font
    {
        $this->woffFilename = StringHandler::cleanForFilename($woffFilename);
        return $this;
    }

    /**
     * @return string
     */
    public function getWOFF2RelativeUrl(): ?string
    {
        return $this->getFolder() . '/' . $this->getWOFF2Filename();
    }

    /**
     * @return string
     */
    public function getWOFF2Filename(): ?string
    {
        return $this->woff2Filename;
    }

    /**
     * @param string $woff2Filename
     *
     * @return $this
     */
    public function setWOFF2Filename(?string $woff2Filename): Font
    {
        $this->woff2Filename = StringHandler::cleanForFilename($woff2Filename);
        return $this;
    }

    /**
     * @return string
     */
    public function getOTFRelativeUrl(): ?string
    {
        return $this->getFolder() . '/' . $this->getOTFFilename();
    }

    /**
     * @return string
     */
    public function getOTFFilename(): ?string
    {
        return $this->otfFilename;
    }

    /**
     * @param string $otfFilename
     * @return $this
     */
    public function setOTFFilename(?string $otfFilename): Font
    {
        $this->otfFilename = StringHandler::cleanForFilename($otfFilename);
        return $this;
    }

    /**
     * @return string
     */
    public function getSVGRelativeUrl(): ?string
    {
        return $this->getFolder() . '/' . $this->getSVGFilename();
    }

    /**
     * @return string
     */
    public function getSVGFilename(): ?string
    {
        return $this->svgFilename;
    }

    /**
     * @param string $svgFilename
     * @return $this
     */
    public function setSVGFilename(?string $svgFilename): Font
    {
        $this->svgFilename = StringHandler::cleanForFilename($svgFilename);
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string $description
     *
     * @return $this
     */
    public function setDescription(?string $description): Font
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Gets the value of eotFile.
     *
     * @return UploadedFile
     */
    public function getEotFile(): ?UploadedFile
    {
        return $this->eotFile;
    }

    /**
     * Sets the value of eotFile.
     *
     * @param UploadedFile $eotFile the eot file
     * @return Font
     */
    public function setEotFile(?UploadedFile $eotFile): Font
    {
        $this->eotFile = $eotFile;
        return $this;
    }

    /**
     * Gets the value of woffFile.
     *
     * @return UploadedFile
     */
    public function getWoffFile(): ?UploadedFile
    {
        return $this->woffFile;
    }

    /**
     * Sets the value of woffFile.
     *
     * @param UploadedFile $woffFile the woff file
     * @return Font
     */
    public function setWoffFile(?UploadedFile $woffFile): Font
    {
        $this->woffFile = $woffFile;
        return $this;
    }

    /**
     * Gets the value of woff2File.
     *
     * @return UploadedFile
     */
    public function getWoff2File(): ?UploadedFile
    {
        return $this->woff2File;
    }

    /**
     * Sets the value of woff2File.
     *
     * @param UploadedFile $woff2File the woff2 file
     * @return Font
     */
    public function setWoff2File(?UploadedFile $woff2File): Font
    {
        $this->woff2File = $woff2File;
        return $this;
    }

    /**
     * Gets the value of otfFile.
     *
     * @return UploadedFile
     */
    public function getOtfFile(): ?UploadedFile
    {
        return $this->otfFile;
    }

    /**
     * Sets the value of otfFile.
     *
     * @param UploadedFile $otfFile the otf file
     * @return Font
     */
    public function setOtfFile(?UploadedFile $otfFile): Font
    {
        $this->otfFile = $otfFile;
        return $this;
    }

    /**
     * Gets the value of svgFile.
     *
     * @return UploadedFile
     */
    public function getSvgFile(): ?UploadedFile
    {
        return $this->svgFile;
    }

    /**
     * Sets the value of svgFile.
     *
     * @param UploadedFile $svgFile the svg file
     * @return Font
     */
    public function setSvgFile(?UploadedFile $svgFile): Font
    {
        $this->svgFile = $svgFile;
        return $this;
    }
}
