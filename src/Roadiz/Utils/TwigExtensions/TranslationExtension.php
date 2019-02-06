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
 * @file TranslationExtension.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Utils\TwigExtensions;

use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Viewers\TranslationViewer;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigTest;

/**
 * Extension that allow render document images
 */
class TranslationExtension extends AbstractExtension
{
    /**
     * @var RequestStack
     */
    private $requestStack;
    /**
     * @var TranslationViewer
     */
    private $translationViewer;

    /**
     * TranslationExtension constructor.
     * @param RequestStack $requestStack
     * @param TranslationViewer $translationViewer
     */
    public function __construct(RequestStack $requestStack, TranslationViewer $translationViewer)
    {
        $this->requestStack = $requestStack;
        $this->translationViewer = $translationViewer;
    }

    public function getFilters()
    {
        return [
            new TwigFilter('menu', [$this, 'getMenuAssignation']),
            new TwigFilter('country_iso', [$this, 'getCountryName']),
        ];
    }

    public function getTests()
    {
        return [
            new TwigTest('rtl', [$this, 'isLocaleRtl'])
        ];
    }

    /**
     * @param string|Translation $mixed
     *
     * @return bool
     */
    public function isLocaleRtl($mixed)
    {
        if ($mixed instanceof Translation) {
            return $mixed->isRtl();
        }

        if (is_string($mixed)) {
            return in_array($mixed, Translation::getRightToLeftLocales());
        }

        return false;
    }

    /**
     * @param Translation|null $translation
     * @param bool $absolute
     * @return array
     */
    public function getMenuAssignation(Translation $translation = null, $absolute = false)
    {
        if (null !== $translation) {
            $this->translationViewer->setTranslation($translation);
            return $this->translationViewer->getTranslationMenuAssignation($this->requestStack->getCurrentRequest(), $absolute);
        } else {
            return [];
        }
    }

    /**
     * @param string $iso
     * @param string $locale
     * @return string
     */
    public function getCountryName($iso, $locale = 'en')
    {
        return \Locale::getDisplayRegion('-'.$iso, $locale);
    }
}
