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
use RZ\Roadiz\Core\AbstractEntities\PositionedInterface;

interface AttributeValueInterface extends PositionedInterface
{
    /**
     * @return AttributeInterface
     */
    public function getAttribute(): AttributeInterface;

    /**
     * @param AttributeInterface $attribute
     *
     * @return mixed
     */
    public function setAttribute(AttributeInterface $attribute);

    /**
     * @return int
     */
    public function getType(): int;

    /**
     * @return Collection<AttributeValueTranslationInterface>
     */
    public function getAttributeValueTranslations(): Collection;

    /**
     * @param Collection<AttributeValueTranslationInterface> $attributeValueTranslations
     *
     * @return mixed
     */
    public function setAttributeValueTranslations(Collection $attributeValueTranslations);

    /**
     * @return AttributableInterface|null
     */
    public function getAttributable(): ?AttributableInterface;

    /**
     * @param AttributableInterface|null $attributable
     *
     * @return mixed
     */
    public function setAttributable(?AttributableInterface $attributable);
}
