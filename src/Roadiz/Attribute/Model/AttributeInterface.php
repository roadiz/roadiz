<?php
declare(strict_types=1);

namespace RZ\Roadiz\Attribute\Model;

use Doctrine\Common\Collections\Collection;
use RZ\Roadiz\Core\AbstractEntities\PersistableInterface;
use RZ\Roadiz\Core\Entities\Translation;

interface AttributeInterface extends PersistableInterface
{
    /**
     * String field is a simple 255 characters long text.
     */
    const STRING_T = 0;
    /**
     * DateTime field is a combined Date and Time.
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
     * Decimal field has a percent for rendering.
     */
    const PERCENT_T = 26;
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
    public function getLabelOrCode(?Translation $translation = null): string;

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
     * @return bool
     */
    public function isSearchable(): bool;

     /**
     * @param bool $searchable
     */
    public function setSearchable(bool $searchable);

    /**
     * @param Translation $translation
     *
     * @return array|null
     */
    public function getOptions(Translation $translation): ?array;

    /**
     * @return int
     */
    public function getType(): int;

    /**
     * @return string|null
     */
    public function getColor(): ?string;

    /**
     * @param string|null $color
     */
    public function setColor(?string $color);

    /**
     * @return AttributeGroupInterface|null
     */
    public function getGroup(): ?AttributeGroupInterface;

    /**
     * @param AttributeGroupInterface|null $group
     */
    public function setGroup(?AttributeGroupInterface $group);

    /**
     * @return Collection
     */
    public function getDocuments(): Collection;

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
    public function isPercent(): bool;

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
