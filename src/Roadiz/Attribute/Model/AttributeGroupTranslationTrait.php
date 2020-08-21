<?php
declare(strict_types=1);

namespace RZ\Roadiz\Attribute\Model;

use RZ\Roadiz\Core\Entities\Translation;

trait AttributeGroupTranslationTrait
{
    /**
     * @var string|null
     */
    protected $name;

    /**
     * @var Translation|null
     */
    protected $translation;

    /**
     * @var AttributeGroupInterface|null
     */
    protected $attributeGroup;

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string|null $value
     *
     * @return self
     */
    public function setName(?string $value)
    {
        $this->name = $value;
        return $this;
    }

    /**
     * @param Translation $translation
     *
     * @return mixed
     */
    public function setTranslation(Translation $translation)
    {
        $this->translation = $translation;
        return $this;
    }

    /**
     * @return Translation|null
     */
    public function getTranslation(): ?Translation
    {
        return $this->translation;
    }

    /**
     * @return AttributeGroupInterface
     */
    public function getAttributeGroup(): AttributeGroupInterface
    {
        return $this->attributeGroup;
    }

    /**
     * @param AttributeGroupInterface $attributeGroup
     *
     * @return mixed
     */
    public function setAttributeGroup(AttributeGroupInterface $attributeGroup)
    {
        $this->attributeGroup = $attributeGroup;
        return $this;
    }
}
