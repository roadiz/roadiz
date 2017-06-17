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
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Extension that allow render document images
 */
class TranslationExtension extends \Twig_Extension
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * TranslationExtension constructor.
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function getName()
    {
        return 'translationExtension';
    }

    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('menu', [$this, 'getMenuAssignation']),
            new \Twig_SimpleFilter('country_iso', [$this, 'getCountryName']),
        ];
    }

    /**
     * @param Translation|null $translation
     * @param bool $absolute
     * @return array
     */
    public function getMenuAssignation(Translation $translation = null, $absolute = false)
    {
        if (null !== $translation) {
            return $translation->getViewer()->getTranslationMenuAssignation($this->requestStack->getCurrentRequest(), $absolute);
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
