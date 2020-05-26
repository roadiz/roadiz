<?php
declare(strict_types=1);

namespace RZ\Roadiz\Attribute\Model;

use RZ\Roadiz\Core\Entities\Translation;

trait AttributeTranslationTrait
{
    /**
     * @return string
     */
    public function getLabel(): ?string
    {
        return $this->label;
    }

    /**
     * @param string $label
     *
     * @return mixed
     */
    public function setLabel(?string $label)
    {
        $this->label = trim($label);
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
     * @return AttributeInterface
     */
    public function getAttribute(): AttributeInterface
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
     * @return array|null
     */
    public function getOptions(): ?array
    {
        return $this->options;
    }

    /**
     * @param array|null $options
     *
     * @return $this
     */
    public function setOptions(?array $options)
    {
        $this->options = $options;
        return $this;
    }
}
