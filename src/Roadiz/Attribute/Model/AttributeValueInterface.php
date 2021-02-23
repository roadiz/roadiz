<?php
declare(strict_types=1);

namespace RZ\Roadiz\Attribute\Model;

use Doctrine\Common\Collections\Collection;
use RZ\Roadiz\Core\AbstractEntities\PersistableInterface;
use RZ\Roadiz\Core\AbstractEntities\PositionedInterface;
use RZ\Roadiz\Core\AbstractEntities\TranslationInterface;

interface AttributeValueInterface extends PositionedInterface, PersistableInterface
{
    /**
     * @return AttributeInterface
     */
    public function getAttribute(): ?AttributeInterface;

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
     * @param TranslationInterface $translation
     *
     * @return AttributeValueTranslationInterface
     */
    public function getAttributeValueTranslation(TranslationInterface $translation): ?AttributeValueTranslationInterface;

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
