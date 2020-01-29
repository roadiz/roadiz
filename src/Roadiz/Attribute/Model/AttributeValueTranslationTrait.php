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
 * @file AttributeValueTranslationTrait.php
 * @author Ambroise Maupate
 *
 */
declare(strict_types=1);

namespace RZ\Roadiz\Attribute\Model;

use RZ\Roadiz\Core\Entities\Translation;

trait AttributeValueTranslationTrait
{
    /**
     * @return mixed
     * @throws \Exception
     */
    public function getValue()
    {
        switch ($this->getAttributeValue()->getType()) {
            case AttributeInterface::DECIMAL_T:
                return (float) $this->value;
            case AttributeInterface::INTEGER_T:
                return (int) $this->value;
            case AttributeInterface::BOOLEAN_T:
                return (bool) $this->value;
            case AttributeInterface::DATETIME_T:
            case AttributeInterface::DATE_T:
                return $this->value ? new \DateTime($this->value) : null;
            default:
                return $this->value;
        }
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    public function setValue($value)
    {
        switch ($this->getAttributeValue()->getType()) {
            case AttributeInterface::EMAIL_T:
                if (false === filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    throw new \InvalidArgumentException('Email is not valid');
                }
                $this->value = $value;
                return $this;
            case AttributeInterface::DECIMAL_T:
                $this->value = (float) $value;
                return $this;
            case AttributeInterface::INTEGER_T:
                $this->value = (int) $value;
                return $this;
            case AttributeInterface::BOOLEAN_T:
                $this->value = (bool) $value;
                return $this;
            case AttributeInterface::DATETIME_T:
            case AttributeInterface::DATE_T:
                if ($value instanceof \DateTime) {
                    $this->value = $value->format('Y-m-d H:i:s');
                } else {
                    $this->value = $value;
                }
                return $this;
            default:
                $this->value = $value;
                return $this;
        }
    }

    /**
     * @param Translation $translation
     *
     * @return mixed
     */
    public function setTranslation(Translation $translation)
    {
        $this->translation = $translation;
        return $this;
    }

    /**
     * @return Translation|null
     */
    public function getTranslation(): ?Translation
    {
        return $this->translation;
    }

    /**
     * @return AttributeValueInterface
     */
    public function getAttributeValue(): AttributeValueInterface
    {
        return $this->attributeValue;
    }

    /**
     * @param AttributeValueInterface $attributeValue
     *
     * @return mixed
     */
    public function setAttributeValue(AttributeValueInterface $attributeValue)
    {
        $this->attributeValue = $attributeValue;
        return $this;
    }
}
