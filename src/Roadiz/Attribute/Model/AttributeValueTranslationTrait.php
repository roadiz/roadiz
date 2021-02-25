<?php
declare(strict_types=1);

namespace RZ\Roadiz\Attribute\Model;

use RZ\Roadiz\Core\AbstractEntities\TranslationInterface;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

trait AttributeValueTranslationTrait
{
    /**
     * @var TranslationInterface|null
     * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Core\AbstractEntities\TranslationInterface")
     * @ORM\JoinColumn(name="translation_id", onDelete="CASCADE", referencedColumnName="id")
     * @Serializer\Groups({"attribute", "node", "nodes_sources"})
     * @Serializer\Type("RZ\Roadiz\Core\AbstractEntities\TranslationInterface")
     * @Serializer\Accessor(getter="getTranslation", setter="setTranslation")
     */
    protected ?TranslationInterface $translation = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", nullable=true, unique=false, length=255)
     * @Serializer\Groups({"attribute", "node", "nodes_sources"})
     * @Serializer\Type("string")
     */
    protected ?string $value = null;

    /**
     * @var AttributeValueInterface|null
     * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Attribute\Model\AttributeValueInterface", inversedBy="attributeValueTranslations", cascade={"persist"})
     * @ORM\JoinColumn(name="attribute_value", onDelete="CASCADE", referencedColumnName="id")
     * @Serializer\Exclude
     */
    protected ?AttributeValueInterface $attributeValue = null;

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
     * @param TranslationInterface $translation
     *
     * @return mixed
     */
    public function setTranslation(TranslationInterface $translation)
    {
        $this->translation = $translation;
        return $this;
    }

    /**
     * @return TranslationInterface|null
     */
    public function getTranslation(): ?TranslationInterface
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
