<?php
declare(strict_types=1);

namespace RZ\Roadiz\Attribute\Model;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use RZ\Roadiz\Core\AbstractEntities\TranslationInterface;

trait AttributeValueTrait
{
    /**
     * @var AttributeInterface|null
     * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Attribute\Model\AttributeInterface", inversedBy="attributeValues", fetch="EAGER")
     * @ORM\JoinColumn(name="attribute_id", onDelete="CASCADE", referencedColumnName="id")
     * @Serializer\Groups({"attribute", "node", "nodes_sources"})
     * @Serializer\Type("RZ\Roadiz\Core\Entities\Attribute")
     */
    protected ?AttributeInterface $attribute = null;

    /**
     * @var Collection<AttributeValueTranslationInterface>
     * @ORM\OneToMany(
     *     targetEntity="RZ\Roadiz\Attribute\Model\AttributeValueTranslationInterface",
     *     mappedBy="attributeValue",
     *     fetch="EAGER",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     * @Serializer\Groups({"attribute", "node", "nodes_sources"})
     * @Serializer\Type("ArrayCollection<RZ\Roadiz\Attribute\Model\AttributeValueTranslationInterface>")
     * @Serializer\Accessor(getter="getAttributeValueTranslations",setter="setAttributeValueTranslations")
     */
    protected Collection $attributeValueTranslations;

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
     * @param TranslationInterface $translation
     *
     * @return AttributeValueTranslationInterface
     */
    public function getAttributeValueTranslation(TranslationInterface $translation): ?AttributeValueTranslationInterface
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
