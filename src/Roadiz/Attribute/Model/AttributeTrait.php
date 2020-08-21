<?php
declare(strict_types=1);

namespace RZ\Roadiz\Attribute\Model;

use Doctrine\Common\Collections\Collection;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Utils\StringHandler;

trait AttributeTrait
{
    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @param string|null $code
     *
     * @return mixed
     */
    public function setCode(?string $code)
    {
        $this->code = StringHandler::slugify($code ?? '');
        return $this;
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @param int $type
     *
     * @return $this
     */
    public function setType(int $type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getColor(): ?string
    {
        return $this->color;
    }

    /**
     * @param string|null $color
     *
     * @return mixed
     */
    public function setColor(?string $color)
    {
        $this->color = $color;
        return $this;
    }

    /**
     * @return AttributeGroupInterface|null
     */
    public function getGroup(): ?AttributeGroupInterface
    {
        return $this->group;
    }

    /**
     * @param AttributeGroupInterface|null $group
     *
     * @return mixed
     */
    public function setGroup(?AttributeGroupInterface $group)
    {
        $this->group = $group;
        return $this;
    }

    /**
     * @return bool
     */
    public function isSearchable(): bool
    {
        return (bool) $this->searchable;
    }

    /**
     * @param bool $searchable
     *
     * @return $this
     */
    public function setSearchable(bool $searchable)
    {
        $this->searchable = $searchable;
        return $this;
    }

    /**
     * @param Translation $translation
     *
     * @return string
     */
    public function getLabelOrCode(?Translation $translation = null): string
    {
        if (null !== $translation) {
            $attributeTranslation = $this->getAttributeTranslations()->filter(
                function (AttributeTranslationInterface $attributeTranslation) use ($translation) {
                    return $attributeTranslation->getTranslation() === $translation;
                }
            );

            if ($attributeTranslation->first() &&
                $attributeTranslation->first()->getLabel() !== '') {
                return $attributeTranslation->first()->getLabel();
            }
        }

        return $this->getCode();
    }

    /**
     * @param Translation $translation
     *
     * @return array|null
     */
    public function getOptions(Translation $translation): ?array
    {
        $attributeTranslation = $this->getAttributeTranslations()->filter(
            function (AttributeTranslationInterface $attributeTranslation) use ($translation) {
                return $attributeTranslation->getTranslation() === $translation;
            }
        );
        if ($attributeTranslation->count() > 0) {
            return $attributeTranslation->first()->getOptions();
        }

        return null;
    }

    /**
     * @return Collection<AttributeTranslationInterface>
     */
    public function getAttributeTranslations(): Collection
    {
        return $this->attributeTranslations;
    }

    /**
     * @param Collection $attributeTranslations
     *
     * @return $this
     */
    public function setAttributeTranslations(Collection $attributeTranslations)
    {
        $this->attributeTranslations = $attributeTranslations;
        /** @var AttributeTranslationInterface $attributeTranslation */
        foreach ($this->attributeTranslations as $attributeTranslation) {
            $attributeTranslation->setAttribute($this);
        }
        return $this;
    }

    /**
     * @param AttributeTranslationInterface $attributeTranslation
     *
     * @return $this
     */
    public function addAttributeTranslation(AttributeTranslationInterface $attributeTranslation)
    {
        if (!$this->getAttributeTranslations()->contains($attributeTranslation)) {
            $this->getAttributeTranslations()->add($attributeTranslation);
            $attributeTranslation->setAttribute($this);
        }
        return $this;
    }

    /**
     * @param AttributeTranslationInterface $attributeTranslation
     *
     * @return mixed
     */
    public function removeAttributeTranslation(AttributeTranslationInterface $attributeTranslation)
    {
        if ($this->getAttributeTranslations()->contains($attributeTranslation)) {
            $this->getAttributeTranslations()->removeElement($attributeTranslation);
        }
        return $this;
    }

    public function isString(): bool
    {
        return $this->getType() === AttributeInterface::STRING_T;
    }

    public function isDate(): bool
    {
        return $this->getType() === AttributeInterface::DATE_T;
    }

    public function isDateTime(): bool
    {
        return $this->getType() === AttributeInterface::DATETIME_T;
    }

    public function isBoolean(): bool
    {
        return $this->getType() === AttributeInterface::BOOLEAN_T;
    }

    public function isInteger(): bool
    {
        return $this->getType() === AttributeInterface::INTEGER_T;
    }

    public function isDecimal(): bool
    {
        return $this->getType() === AttributeInterface::DECIMAL_T;
    }

    public function isPercent(): bool
    {
        return $this->getType() === AttributeInterface::PERCENT_T;
    }

    public function isEmail(): bool
    {
        return $this->getType() === AttributeInterface::EMAIL_T;
    }

    public function isColor(): bool
    {
        return $this->getType() === AttributeInterface::COLOUR_T;
    }

    public function isColour(): bool
    {
        return $this->isColor();
    }

    public function isEnum(): bool
    {
        return $this->getType() === AttributeInterface::ENUM_T;
    }

    public function isCountry(): bool
    {
        return $this->getType() === AttributeInterface::COUNTRY_T;
    }
}
