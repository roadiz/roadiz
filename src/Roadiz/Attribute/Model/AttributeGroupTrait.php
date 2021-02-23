<?php
declare(strict_types=1);

namespace RZ\Roadiz\Attribute\Model;

use Doctrine\Common\Collections\Collection;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Utils\StringHandler;

/**
 * Trait AttributeGroupTrait
 *
 * @package RZ\Roadiz\Attribute\Model
 */
trait AttributeGroupTrait
{
    /**
     * @var string
     */
    protected string $canonicalName = '';
    /**
     * @var Collection<AttributeInterface>
     */
    protected Collection $attributes;

    public function getName(): ?string
    {
        if ($this->getAttributeGroupTranslations()->first()) {
            return $this->getAttributeGroupTranslations()->first()->getName();
        }
        return $this->getCanonicalName();
    }

    public function getTranslatedName(?Translation $translation): ?string
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
