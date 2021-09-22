<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\TwigExtensions;

use RZ\Roadiz\Core\Entities\Font;
use RZ\Roadiz\Utils\Asset\Packages;
use Twig\Error\RuntimeError;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Extension that allow render fonts.
 */
final class FontExtension extends AbstractExtension
{
    private Packages $assetPackages;

    /**
     * @param Packages $assetPackages
     */
    public function __construct(Packages $assetPackages)
    {
        $this->assetPackages = $assetPackages;
    }

    /**
     * @return array
     */
    public function getFilters()
    {
        return [
            new TwigFilter('eotPath', [$this, 'getEotFilePath']),
            new TwigFilter('ttfPath', [$this, 'getTtfFilePath']),
            new TwigFilter('otfPath', [$this, 'getTtfFilePath']),
            new TwigFilter('svgPath', [$this, 'getSvgFilePath']),
            new TwigFilter('woffPath', [$this, 'getWoffFilePath']),
            new TwigFilter('woff2Path', [$this, 'getWoff2FilePath']),
        ];
    }

    /**
     * @param Font|null $font
     * @return string
     * @throws RuntimeError
     */
    public function getEotFilePath(Font $font = null)
    {
        if (null === $font) {
            throw new RuntimeError('Font can’t be null.');
        }
        return $this->assetPackages->getFontsPath($font->getEOTRelativeUrl());
    }

    /**
     * @param Font|null $font
     * @return string
     * @throws RuntimeError
     */
    public function getTtfFilePath(Font $font = null)
    {
        if (null === $font) {
            throw new RuntimeError('Font can’t be null.');
        }
        return $this->assetPackages->getFontsPath($font->getOTFRelativeUrl());
    }

    /**
     * @param Font|null $font
     * @return string
     * @throws RuntimeError
     */
    public function getSvgFilePath(Font $font = null)
    {
        if (null === $font) {
            throw new RuntimeError('Font can’t be null.');
        }
        return $this->assetPackages->getFontsPath($font->getSVGRelativeUrl());
    }

    /**
     * @param Font|null $font
     * @return string
     * @throws RuntimeError
     */
    public function getWoffFilePath(Font $font = null)
    {
        if (null === $font) {
            throw new RuntimeError('Font can’t be null.');
        }
        return $this->assetPackages->getFontsPath($font->getWOFFRelativeUrl());
    }

    /**
     * @param Font|null $font
     * @return string
     * @throws RuntimeError
     */
    public function getWoff2FilePath(Font $font = null)
    {
        if (null === $font) {
            throw new RuntimeError('Font can’t be null.');
        }
        return $this->assetPackages->getFontsPath($font->getWOFF2RelativeUrl());
    }
}
