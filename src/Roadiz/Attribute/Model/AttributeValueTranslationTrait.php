<?php
declare(strict_types=1);

namespace RZ\Roadiz\Attribute\Model;

use RZ\Roadiz\Core\Entities\Translation;

trait AttributeValueTranslationTrait
{
    /**
     * @return mixed
     * @throws \Exception
     */
    public function getValue()
    {
        switch ($this->getAttributeValue()->getType()) {
            case AttributeInterface::DECIMAL_T:
                return (float) $this->value;
            case AttributeInterface::INTEGER_T:
                return (int) $this->value;
            case AttributeInterface::BOOLEAN_T:
                return (bool) $this->value;
            case AttributeInterface::DATETIME_T:
            case AttributeInterface::DATE_T:
                return $this->value ? new \DateTime($this->value) : null;
            default:
                return $this->value;
        }
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    public function setValue($value)
    {
        switch ($this->getAttributeValue()->getType()) {
            case AttributeInterface::EMAIL_T:
                if (false === filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    throw new \InvalidArgumentException('Email is not valid');
                }
                $this->value = $value;
                return $this;
            case AttributeInterface::DECIMAL_T:
                $this->value = (float) $value;
                return $this;
            case AttributeInterface::INTEGER_T:
                $this->value = (int) $value;
                return $this;
            case AttributeInterface::BOOLEAN_T:
                $this->value = (bool) $value;
                return $this;
            case AttributeInterface::DATETIME_T:
            case AttributeInterface::DATE_T:
                if ($value instanceof \DateTime) {
                    $this->value = $value->format('Y-m-d H:i:s');
                } else {
                    $this->value = $value;
                }
                return $this;
            default:
                $this->value = $value;
                return $this;
        }
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
     * @return AttributeValueInterface
     */
    public function getAttributeValue(): AttributeValueInterface
    {
        return $this->attributeValue;
    }

    /**
     * @param AttributeValueInterface $attributeValue
     *
     * @return mixed
     */
    public function setAttributeValue(AttributeValueInterface $attributeValue)
    {
        $this->attributeValue = $attributeValue;
        return $this;
    }

    /**
     * @return AttributeInterface
     */
    public function getAttribute(): ?AttributeInterface
    {
        return $this->getAttributeValue()->getAttribute();
    }
}
