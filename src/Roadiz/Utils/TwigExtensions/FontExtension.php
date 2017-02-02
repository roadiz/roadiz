<?php
/**
 * Copyright © 2015, Ambroise Maupate and Julien Blanchet
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
 * @file FontExtension.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Utils\TwigExtensions;

use RZ\Roadiz\Core\Entities\Font;
use RZ\Roadiz\Utils\Asset\Packages;

/**
 * Extension that allow render fonts.
 */
class FontExtension extends \Twig_Extension
{
    /**
     * @var Packages
     */
    private $packages;

    /**
     * DocumentExtension constructor.
     * @param Packages $packages
     */
    public function __construct(Packages $packages)
    {
        $this->packages = $packages;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'fontExtension';
    }

    /**
     * @return array
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('eotPath', [$this, 'getEotFilePath']),
            new \Twig_SimpleFilter('ttfPath', [$this, 'getTtfFilePath']),
            new \Twig_SimpleFilter('otfPath', [$this, 'getTtfFilePath']),
            new \Twig_SimpleFilter('svgPath', [$this, 'getSvgFilePath']),
            new \Twig_SimpleFilter('woffPath', [$this, 'getWoffFilePath']),
            new \Twig_SimpleFilter('woff2Path', [$this, 'getWoff2FilePath']),
        ];
    }

    /**
     * @param Font $font
     * @return string
     * @throws \Twig_Error_Runtime
     */
    public function getEotFilePath(Font $font = null)
    {
        if (null === $font) {
            throw new \Twig_Error_Runtime('Font can’t be null.');
        }
        return $this->packages->getFontsPath($font->getEOTRelativeUrl());
    }

    /**
     * @param Font $font
     * @return string
     * @throws \Twig_Error_Runtime
     */
    public function getTtfFilePath(Font $font = null)
    {
        if (null === $font) {
            throw new \Twig_Error_Runtime('Font can’t be null.');
        }
        return $this->packages->getFontsPath($font->getOTFRelativeUrl());
    }

    /**
     * @param Font $font
     * @return string
     * @throws \Twig_Error_Runtime
     */
    public function getSvgFilePath(Font $font = null)
    {
        if (null === $font) {
            throw new \Twig_Error_Runtime('Font can’t be null.');
        }
        return $this->packages->getFontsPath($font->getSVGRelativeUrl());
    }

    /**
     * @param Font $font
     * @return string
     * @throws \Twig_Error_Runtime
     */
    public function getWoffFilePath(Font $font = null)
    {
        if (null === $font) {
            throw new \Twig_Error_Runtime('Font can’t be null.');
        }
        return $this->packages->getFontsPath($font->getWOFFRelativeUrl());
    }

    /**
     * @param Font $font
     * @return string
     * @throws \Twig_Error_Runtime
     */
    public function getWoff2FilePath(Font $font = null)
    {
        if (null === $font) {
            throw new \Twig_Error_Runtime('Font can’t be null.');
        }
        return $this->packages->getFontsPath($font->getWOFF2RelativeUrl());
    }
}
