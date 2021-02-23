<?php
declare(strict_types=1);

namespace RZ\Roadiz\Attribute\Model;

use Doctrine\Common\Collections\Collection;
use RZ\Roadiz\Core\AbstractEntities\TranslationInterface;
use RZ\Roadiz\Utils\StringHandler;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

trait AttributeTrait
{
    /**
     * @var string
     * @ORM\Column(type="string", nullable=false, unique=true)
     * @Serializer\Groups({"attribute", "node", "nodes_sources"})
     * @Serializer\Type("string")
     */
    protected string $code = '';

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false, unique=false, options={"default" = false})
     * @Serializer\Groups({"attribute"})
     * @Serializer\Type("boolean")
     */
    protected bool $searchable = false;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=false, unique=false)
     * @Serializer\Groups({"attribute"})
     * @Serializer\Type("integer")
     */
    protected int $type = AttributeInterface::STRING_T;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=7, nullable=true, unique=false)
     * @Serializer\Groups({"attribute", "node", "nodes_sources"})
     * @Serializer\Type("string")
     */
    protected ?string $color = null;

    /**
     * @var AttributeGroupInterface|null
     * @ORM\ManyToOne(
     *     targetEntity="RZ\Roadiz\Attribute\Model\AttributeGroupInterface",
     *     inversedBy="attributes",
     *     fetch="EAGER",
     *     cascade={"persist", "merge"}
     * )
     * @ORM\JoinColumn(name="group_id", onDelete="SET NULL")
     * @Serializer\Groups({"attribute", "node", "nodes_sources"})
     * @Serializer\Type("RZ\Roadiz\Attribute\Model\AttributeGroupInterface")
     */
    protected ?AttributeGroupInterface $group = null;

    /**
     * @var Collection<AttributeTranslationInterface>
     * @ORM\OneToMany(
     *     targetEntity="RZ\Roadiz\Attribute\Model\AttributeTranslationInterface",
     *     mappedBy="attribute",
     *     fetch="EAGER",
     *     cascade={"all"},
     *     orphanRemoval=true
     * )
     * @Serializer\Groups({"attribute", "node", "nodes_sources"})
     * @Serializer\Type("ArrayCollection<RZ\Roadiz\Attribute\Model\AttributeTranslationInterface>")
     * @Serializer\Accessor(getter="getAttributeTranslations",setter="setAttributeTranslations")
     */
    protected Collection $attributeTranslations;

    /**
     * @var Collection<AttributeValueInterface>
     * @ORM\OneToMany(
     *     targetEntity="RZ\Roadiz\Attribute\Model\AttributeValueInterface",
     *     mappedBy="attribute",
     *     fetch="EXTRA_LAZY",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     * @Serializer\Exclude
     */
    protected Collection $attributeValues;

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
     * @param TranslationInterface|null $translation
     *
     * @return string
     */
    public function getLabelOrCode(?TranslationInterface $translation = null): string
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
     * @param TranslationInterface $translation
     *
     * @return array|null
     */
    public function getOptions(TranslationInterface $translation): ?array
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
