<?php
/**
 * Copyright © 2019, Ambroise Maupate and Julien Blanchet
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
 * Except as contained in this notice, the name of the roadiz shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file AttributesExtension.php
 * @author Ambroise Maupate
 *
 */
declare(strict_types=1);

namespace RZ\Roadiz\Attribute\Twig;

use RZ\Roadiz\Attribute\Model\AttributableInterface;
use RZ\Roadiz\Attribute\Model\AttributeInterface;
use RZ\Roadiz\Attribute\Model\AttributeValueInterface;
use RZ\Roadiz\Attribute\Model\AttributeValueTranslationInterface;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\Translation;
use Twig\Error\SyntaxError;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AttributesExtension extends AbstractExtension
{
    public function getFunctions()
    {
        return [
            new TwigFunction('get_attributes', [$this, 'getAttributeValues']),
            new TwigFunction('node_source_attributes', [$this, 'getNodeSourceAttributeValues']),
        ];
    }

    public function getFilters()
    {
        return [
            new TwigFilter('attributes', [$this, 'getNodeSourceAttributeValues']),
            new TwigFilter('label', [$this, 'getAttributeLabelOrCode']),
        ];
    }


    /**
     * @param AttributableInterface $attributable
     * @param Translation      $translation
     *
     * @return \Doctrine\Common\Collections\Collection
     * @throws SyntaxError
     */
    public function getAttributeValues($attributable, Translation $translation)
    {
        if (null === $attributable) {
            throw new SyntaxError('Cannot call get_attributes on NULL');
        }
        if (!$attributable instanceof AttributableInterface) {
            throw new SyntaxError('get_attributes only accepts entities that implement AttributableInterface');
        }

        return $attributable->getAttributesValuesTranslations($translation);
    }

    /**
     * @param NodesSources|null $nodesSources
     *
     * @return \Doctrine\Common\Collections\Collection
     * @throws SyntaxError
     */
    public function getNodeSourceAttributeValues(?NodesSources $nodesSources)
    {
        if (null === $nodesSources) {
            throw new SyntaxError('Cannot call node_source_attributes on NULL');
        }
        return $this->getAttributeValues($nodesSources->getNode(), $nodesSources->getTranslation());
    }

    /**
     * @param                  $mixed
     * @param Translation|null $translation
     *
     * @return string|null
     */
    public function getAttributeLabelOrCode($mixed, Translation $translation = null): ?string
    {
        if (null === $mixed) {
            return null;
        }

        if ($mixed instanceof AttributeInterface) {
            return $mixed->getLabelOrCode($translation);
        }
        if ($mixed instanceof AttributeValueInterface) {
            return $mixed->getAttribute()->getLabelOrCode($translation);
        }
        if ($mixed instanceof AttributeValueTranslationInterface) {
            return $mixed->getAttributeValue()->getAttribute()->getLabelOrCode($mixed->getTranslation());
        }

        return null;
    }
}
