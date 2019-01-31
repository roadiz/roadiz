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
 * @file AttributeInterface.php
 * @author Ambroise Maupate
 *
 */
declare(strict_types=1);

namespace RZ\Roadiz\Attribute\Model;

use Doctrine\Common\Collections\Collection;
use RZ\Roadiz\Core\Entities\Translation;

interface AttributeInterface
{
    /**
     * String field is a simple 255 characters long text.
     */
    const STRING_T = 0;
    /**
     * DateTime field is a combined Date and Time.
     *
     * @see \DateTime
     */
    const DATETIME_T = 1;
    /**
     * Boolean field is a simple switch between 0 and 1.
     */
    const BOOLEAN_T = 5;
    /**
     * Integer field is a non-floating number.
     */
    const INTEGER_T = 6;
    /**
     * Decimal field is a floating number.
     */
    const DECIMAL_T = 7;
    /**
     * Email field is a short text which must
     * comply with email rules.
     */
    const EMAIL_T = 8;
    /**
     * Colour field is an hexadecimal string which is rendered
     * with a colour chooser.
     */
    const COLOUR_T = 11;
    /**
     * Enum field is a simple select box with default values.
     */
    const ENUM_T = 15;
    /**
     * @see \DateTime
     */
    const DATE_T = 22;
    /**
     * ISO Country
     */
    const COUNTRY_T = 25;

    /**
     * @return string
     */
    public function getCode(): string;

    /**
     * @param string $code
     *
     * @return mixed
     */
    public function setCode(string $code);

    /**
     * @param Translation $translation
     *
     * @return string
     */
    public function getLabelOrCode(?Translation $translation): string;

    /**
     * @return Collection<AttributeTranslationInterface>
     */
    public function getAttributeTranslations(): Collection;

    /**
     * @param Collection<AttributeTranslationInterface> $attributeTranslations
     *
     * @return mixed
     */
    public function setAttributeTranslations(Collection $attributeTranslations);

    /**
     * @param AttributeTranslationInterface $attributeTranslation
     *
     * @return mixed
     */
    public function addAttributeTranslation(AttributeTranslationInterface $attributeTranslation);

    /**
     * @param AttributeTranslationInterface $attributeTranslation
     *
     * @return mixed
     */
    public function removeAttributeTranslation(AttributeTranslationInterface $attributeTranslation);

    /**
     * @return int
     */
    public function getType(): int;

    /**
     * @param int $type
     *
     * @return mixed
     */
    public function setType(int $type);

    /**
     * @return bool
     */
    public function isString(): bool;

    /**
     * @return bool
     */
    public function isDate(): bool;

    /**
     * @return bool
     */
    public function isDateTime(): bool;

    /**
     * @return bool
     */
    public function isBoolean(): bool;

    /**
     * @return bool
     */
    public function isInteger(): bool;

    /**
     * @return bool
     */
    public function isDecimal(): bool;

    /**
     * @return bool
     */
    public function isEmail(): bool;

    /**
     * @return bool
     */
    public function isColor(): bool;

    /**
     * @return bool
     */
    public function isColour(): bool;

    /**
     * @return bool
     */
    public function isEnum(): bool;

    /**
     * @return bool
     */
    public function isCountry(): bool;
}
