<?php
declare(strict_types=1);

namespace RZ\Roadiz\Attribute\Model;

use Doctrine\Common\Collections\Collection;
use RZ\Roadiz\Core\AbstractEntities\TranslationInterface;
use RZ\Roadiz\Utils\StringHandler;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * @package RZ\Roadiz\Attribute\Model
 */
trait AttributeGroupTrait
{
    /**
     * @var string
     * @ORM\Column(type="string", name="canonical_name", nullable=false, unique=true)
     * @Serializer\Groups({"attribute_group", "attribute", "node", "nodes_sources"})
     * @Serializer\Type("string")
     */
    protected string $canonicalName = '';

    /**
     * @var Collection<AttributeInterface>
     * @ORM\OneToMany(targetEntity="RZ\Roadiz\Attribute\Model\AttributeInterface", mappedBy="group")
     * @Serializer\Groups({"attribute_group"})
     * @Serializer\Type("ArrayCollection<RZ\Roadiz\Attribute\Model\AttributeInterface>")
     */
    protected Collection $attributes;

    /**
     * @var Collection<AttributeGroupTranslationInterface>
     * @ORM\OneToMany(targetEntity="RZ\Roadiz\Attribute\Model\AttributeGroupTranslationInterface", mappedBy="attributeGroup", cascade={"all"})
     * @Serializer\Groups({"attribute_group", "attribute", "node", "nodes_sources"})
     * @Serializer\Type("ArrayCollection<RZ\Roadiz\Attribute\Model\AttributeGroupTranslationInterface>")
     * @Serializer\Accessor(getter="getAttributeGroupTranslations", setter="setAttributeGroupTranslations")
     */
    protected Collection $attributeGroupTranslations;

    public function getName(): ?string
    {
        if ($this->getAttributeGroupTranslations()->first()) {
            return $this->getAttributeGroupTranslations()->first()->getName();
        }
        return $this->getCanonicalName();
    }

    public function getTranslatedName(?TranslationInterface $translation): ?string
    {
        if (null === $translation) {
            return $this->getName();
        }

        $attributeGroupTranslation = $this->getAttributeGroupTranslations()->filter(
            function (AttributeGroupTranslationInterface $attributeGroupTranslation) use ($translation) {
                if ($attributeGroupTranslation->getTranslation() === $translation) {
                    return true;
                }
                return false;
            }
        );
        if ($attributeGroupTranslation->count() > 0 && $attributeGroupTranslation->first()->getName() !== '') {
            return $attributeGroupTranslation->first()->getName();
        }
        return $this->getCanonicalName();
    }

    public function setName(?string $name)
    {
        if ($this->getAttributeGroupTranslations()->count() === 0) {
            $this->getAttributeGroupTranslations()->add(
                $this->createAttributeGroupTranslation()->setName($name)
            );
        }

        $this->canonicalName = StringHandler::slugify($name ?? '');
        return $this;
    }

    public function getCanonicalName(): ?string
    {
        return $this->canonicalName;
    }

    public function setCanonicalName(?string $canonicalName)
    {
        $this->canonicalName = StringHandler::slugify($canonicalName ?? '');
        return $this;
    }

    public function getAttributes(): Collection
    {
        return $this->attributes;
    }

    public function setAttributes(Collection $attributes)
    {
        $this->attributes = $attributes;
        return $this;
    }

    public function getAttributeGroupTranslations(): Collection
    {
        return $this->attributeGroupTranslations;
    }

    /**
     * @param Collection $attributeGroupTranslations
     *
     * @return $this
     */
    public function setAttributeGroupTranslations(Collection $attributeGroupTranslations)
    {
        $this->attributeGroupTranslations = $attributeGroupTranslations;
        /** @var AttributeGroupTranslationInterface $attributeGroupTranslation */
        foreach ($this->attributeGroupTranslations as $attributeGroupTranslation) {
            $attributeGroupTranslation->setAttributeGroup($this);
        }
        return $this;
    }

    /**
     * @param AttributeGroupTranslationInterface $attributeGroupTranslation
     *
     * @return $this
     */
    public function addAttributeGroupTranslation(AttributeGroupTranslationInterface $attributeGroupTranslation)
    {
        if (!$this->getAttributeGroupTranslations()->contains($attributeGroupTranslation)) {
            $this->getAttributeGroupTranslations()->add($attributeGroupTranslation);
            $attributeGroupTranslation->setAttributeGroup($this);
        }
        return $this;
    }

    /**
     * @param AttributeGroupTranslationInterface $attributeGroupTranslation
     *
     * @return mixed
     */
    public function removeAttributeGroupTranslation(AttributeGroupTranslationInterface $attributeGroupTranslation)
    {
        if ($this->getAttributeGroupTranslations()->contains($attributeGroupTranslation)) {
            $this->getAttributeGroupTranslations()->removeElement($attributeGroupTranslation);
        }
        return $this;
    }

    abstract protected function createAttributeGroupTranslation(): AttributeGroupTranslationInterface;
}
