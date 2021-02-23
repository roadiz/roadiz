<?php
declare(strict_types=1);

namespace RZ\Roadiz\Attribute\Model;

use RZ\Roadiz\Core\AbstractEntities\PersistableInterface;
use RZ\Roadiz\Core\AbstractEntities\TranslationInterface;

interface AttributeTranslationInterface extends PersistableInterface
{
    /**
     * @return string|null
     */
    public function getLabel(): ?string;

    /**
     * @param string|null $label
     *
     * @return mixed
     */
    public function setLabel(?string $label);

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
     * @return AttributeInterface
     */
    public function getAttribute(): AttributeInterface;

    /**
     * @param AttributeInterface $attribute
     *
     * @return mixed
     */
    public function setAttribute(AttributeInterface $attribute);


    /**
     * @return array|null
     */
    public function getOptions(): ?array;

    /**
     * @param array|null $options
     *
     * @return mixed
     */
    public function setOptions(?array $options);
}
