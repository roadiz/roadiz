<?php
declare(strict_types=1);

namespace RZ\Roadiz\Attribute\Model;

use Doctrine\Common\Collections\Collection;
use RZ\Roadiz\Core\AbstractEntities\PersistableInterface;
use RZ\Roadiz\Core\Entities\Translation;

interface AttributableInterface extends PersistableInterface
{
    /**
     * @return Collection
     */
    public function getAttributeValues(): Collection;

    /**
     * @param Translation $translation
     *
     * @return Collection<AttributeValueInterface>
     */
    public function getAttributesValuesForTranslation(Translation $translation): Collection;

    /**
     * @param Translation $translation
     *
     * @return Collection<AttributeValueTranslationInterface>
     */
    public function getAttributesValuesTranslations(Translation $translation): Collection;

    /**
     * @param Collection $attributes
     *
     * @return mixed
     */
    public function setAttributeValues(Collection $attributes);

    /**
     * @param AttributeValueInterface $attribute
     *
     * @return mixed
     */
    public function addAttributeValue(AttributeValueInterface $attribute);

    /**
     * @param AttributeValueInterface $attribute
     *
     * @return mixed
     */
    public function removeAttributeValue(AttributeValueInterface $attribute);
}
