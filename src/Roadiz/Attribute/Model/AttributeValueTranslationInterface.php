<?php
declare(strict_types=1);

namespace RZ\Roadiz\Attribute\Model;

use RZ\Roadiz\Core\AbstractEntities\PersistableInterface;
use RZ\Roadiz\Core\Entities\Translation;

interface AttributeValueTranslationInterface extends PersistableInterface
{
    /**
     * @return mixed
     */
    public function getValue();

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    public function setValue($value);

    /**
     * @param Translation $translation
     *
     * @return mixed
     */
    public function setTranslation(Translation $translation);

    /**
     * @return Translation|null
     */
    public function getTranslation(): ?Translation;

    /**
     * @return AttributeInterface|null
     */
    public function getAttribute(): ?AttributeInterface;

    /**
     * @return AttributeValueInterface
     */
    public function getAttributeValue(): AttributeValueInterface;

    /**
     * @param AttributeValueInterface $attributeValue
     *
     * @return mixed
     */
    public function setAttributeValue(AttributeValueInterface $attributeValue);
}
