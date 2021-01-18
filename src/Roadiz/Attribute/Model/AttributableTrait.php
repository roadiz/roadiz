<?php
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
        /** @var Collection<AttributeValueTranslationInterface> $values */
        $values = $this->getAttributesValuesForTranslation($translation)
            ->map(function (AttributeValueInterface $attributeValue) use ($translation) {
                /** @var AttributeValueTranslationInterface $attributeValueTranslation */
                foreach ($attributeValue->getAttributeValueTranslations() as $attributeValueTranslation) {
                    if ($attributeValueTranslation->getTranslation() === $translation) {
                        return $attributeValueTranslation;
                    }
                }
                return null;
            })
            ->filter(function (?AttributeValueTranslationInterface $attributeValueTranslation) {
                return null !== $attributeValueTranslation;
            })
        ;
        return $values; // phpstan does not understand return type after filtering
    }

    /**
     * @param Collection $attributes
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
