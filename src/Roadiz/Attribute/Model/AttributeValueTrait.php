<?php
declare(strict_types=1);

namespace RZ\Roadiz\Attribute\Model;

use Doctrine\Common\Collections\Collection;
use RZ\Roadiz\Core\Entities\Translation;

trait AttributeValueTrait
{
    /**
     * @return AttributeInterface
     */
    public function getAttribute(): ?AttributeInterface
    {
        return $this->attribute;
    }

    /**
     * @param AttributeInterface $attribute
     *
     * @return mixed
     */
    public function setAttribute(AttributeInterface $attribute)
    {
        $this->attribute = $attribute;
        return $this;
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->getAttribute()->getType();
    }

    /**
     * @return Collection<AttributeValueTranslationInterface>
     */
    public function getAttributeValueTranslations(): Collection
    {
        return $this->attributeValueTranslations;
    }

    /**
     * @param Collection $attributeValueTranslations
     *
     * @return mixed
     */
    public function setAttributeValueTranslations(Collection $attributeValueTranslations)
    {
        $this->attributeValueTranslations = $attributeValueTranslations;
        /** @var AttributeValueTranslationInterface $attributeValueTranslation */
        foreach ($this->attributeValueTranslations as $attributeValueTranslation) {
            $attributeValueTranslation->setAttributeValue($this);
        }
        return true;
    }

    /**
     * @param Translation $translation
     *
     * @return AttributeValueTranslationInterface
     */
    public function getAttributeValueTranslation(Translation $translation): ?AttributeValueTranslationInterface
    {
        return $this->getAttributeValueTranslations()
            ->filter(function (AttributeValueTranslationInterface $attributeValueTranslation) use ($translation) {
                if ($attributeValueTranslation->getTranslation() === $translation) {
                    return true;
                }
                return false;
            })
            ->first() ?: null;
    }
}
