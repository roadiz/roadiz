<?php
declare(strict_types=1);

namespace RZ\Roadiz\Attribute\Model;

use Doctrine\Common\Collections\Collection;
use RZ\Roadiz\Core\AbstractEntities\PersistableInterface;
use RZ\Roadiz\Core\AbstractEntities\TranslationInterface;

interface AttributableInterface extends PersistableInterface
{
    /**
     * @return Collection
     */
    public function getAttributeValues(): Collection;

    /**
     * @param TranslationInterface $translation
     *
     * @return Collection<AttributeValueInterface>
     */
    public function getAttributesValuesForTranslation(TranslationInterface $translation): Collection;

    /**
     * @param TranslationInterface $translation
     *
     * @return Collection<AttributeValueTranslationInterface>
     */
    public function getAttributesValuesTranslations(TranslationInterface $translation): Collection;

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
