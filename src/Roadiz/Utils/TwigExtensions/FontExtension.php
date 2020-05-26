<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\TwigExtensions;

use Pimple\Container;
use RZ\Roadiz\Core\Entities\Font;
use Twig\Error\RuntimeError;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Extension that allow render fonts.
 */
class FontExtension extends AbstractExtension
{
    /**
     * @var Container
     */
    private $container;

    /**
     * DocumentExtension constructor.
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
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
     * @param Font $font
     * @return string
     * @throws RuntimeError
     */
    public function getEotFilePath(Font $font = null)
    {
        if (null === $font) {
            throw new RuntimeError('Font can’t be null.');
        }
        return $this->container['assetPackages']->getFontsPath($font->getEOTRelativeUrl());
    }

    /**
     * @param Font $font
     * @return string
     * @throws RuntimeError
     */
    public function getTtfFilePath(Font $font = null)
    {
        if (null === $font) {
            throw new RuntimeError('Font can’t be null.');
        }
        return $this->container['assetPackages']->getFontsPath($font->getOTFRelativeUrl());
    }

    /**
     * @param Font $font
     * @return string
     * @throws RuntimeError
     */
    public function getSvgFilePath(Font $font = null)
    {
        if (null === $font) {
            throw new RuntimeError('Font can’t be null.');
        }
        return $this->container['assetPackages']->getFontsPath($font->getSVGRelativeUrl());
    }

    /**
     * @param Font $font
     * @return string
     * @throws RuntimeError
     */
    public function getWoffFilePath(Font $font = null)
    {
        if (null === $font) {
            throw new RuntimeError('Font can’t be null.');
        }
        return $this->container['assetPackages']->getFontsPath($font->getWOFFRelativeUrl());
    }

    /**
     * @param Font $font
     * @return string
     * @throws RuntimeError
     */
    public function getWoff2FilePath(Font $font = null)
    {
        if (null === $font) {
            throw new RuntimeError('Font can’t be null.');
        }
        return $this->container['assetPackages']->getFontsPath($font->getWOFF2RelativeUrl());
    }
}
