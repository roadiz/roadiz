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
 * @file AttributableTrait.php
 * @author Ambroise Maupate
 *
 */
declare(strict_types=1);

namespace RZ\Roadiz\Attribute\Model;

use Doctrine\Common\Collections\Collection;
use RZ\Roadiz\Core\Entities\Translation;

trait AttributableTrait
{
    /**
     * @return Collection<AttributeValueInterface>
     */
    public function getAttributeValues(): Collection
    {
        return $this->attributeValues;
    }

    /**
     * @param Translation $translation
     *
     * @return Collection<AttributeValueInterface>
     */
    public function getAttributesValuesForTranslation(Translation $translation): Collection
    {
        return $this->getAttributeValues()->filter(function (AttributeValueInterface $attributeValue) use ($translation) {
            /** @var AttributeValueTranslationInterface $attributeValueTranslation */
            foreach ($attributeValue->getAttributeValueTranslations() as $attributeValueTranslation) {
                if ($attributeValueTranslation->getTranslation() === $translation) {
                    return true;
                }
            }
            return false;
        });
    }

    /**
     * @param Translation $translation
     *
     * @return Collection<AttributeValueTranslationInterface>
     */
    public function getAttributesValuesTranslations(Translation $translation): Collection
    {
        return $this->getAttributesValuesForTranslation($translation)
            ->map(function (AttributeValueInterface $attributeValue) use ($translation) {
                /** @var AttributeValueTranslationInterface $attributeValueTranslation */
                foreach ($attributeValue->getAttributeValueTranslations() as $attributeValueTranslation) {
                    if ($attributeValueTranslation->getTranslation() === $translation) {
                        return $attributeValueTranslation;
                    }
                }
                return null;
            })
        ;
    }

    /**
     * @param Collection<AttributeValueInterface> $attributes
     *
     * @return mixed
     */
    public function setAttributeValues(Collection $attributes)
    {
        $this->attributeValues = $attributes;
        return $this;
    }

    /**
     * @param AttributeValueInterface $attribute
     *
     * @return mixed
     */
    public function addAttributeValue(AttributeValueInterface $attribute)
    {
        if (!$this->getAttributeValues()->contains($attribute)) {
            $this->getAttributeValues()->add($attribute);
        }
        return $this;
    }


    /**
     * @param AttributeValueInterface $attribute
     *
     * @return mixed
     */
    public function removeAttributeValue(AttributeValueInterface $attribute)
    {
        if ($this->getAttributeValues()->contains($attribute)) {
            $this->getAttributeValues()->removeElement($attribute);
        }
        return $this;
    }
}
