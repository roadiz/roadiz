<?php
declare(strict_types=1);

namespace RZ\Roadiz\Attribute\Model;

use RZ\Roadiz\Core\AbstractEntities\PersistableInterface;
use RZ\Roadiz\Core\AbstractEntities\TranslationInterface;

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
     * @param TranslationInterface $translation
     *
     * @return mixed
     */
    public function setTranslation(TranslationInterface $translation);

    /**
     * @return TranslationInterface|null
     */
    public function getTranslation(): ?TranslationInterface;

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
