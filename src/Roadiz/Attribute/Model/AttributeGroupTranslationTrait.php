<?php
declare(strict_types=1);

namespace RZ\Roadiz\Attribute\Model;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use RZ\Roadiz\Core\AbstractEntities\TranslationInterface;

trait AttributeGroupTranslationTrait
{
    /**
     * @var TranslationInterface|null
     * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Core\AbstractEntities\TranslationInterface")
     * @ORM\JoinColumn(onDelete="CASCADE", name="translation_id")
     * @Serializer\Groups({"attribute_group", "attribute", "node", "nodes_sources"})
     * @Serializer\Type("RZ\Roadiz\Core\AbstractEntities\TranslationInterface")
     * @Serializer\Accessor(getter="getTranslation", setter="setTranslation")
     */
    protected ?TranslationInterface $translation = null;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=false, unique=false)
     * @Serializer\Groups({"attribute_group", "attribute", "node", "nodes_sources"})
     * @Serializer\Type("string")
     */
    protected string $name = '';

    /**
     * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Attribute\Model\AttributeGroupInterface", inversedBy="attributeGroupTranslations", cascade={"persist"})
     * @ORM\JoinColumn(onDelete="CASCADE", referencedColumnName="id", name="attribute_group_id", nullable=true)
     * @Serializer\Exclude
     */
    protected ?AttributeGroupInterface $attributeGroup = null;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $value
     *
     * @return self
     */
    public function setName(string $value)
    {
        $this->name = $value;
        return $this;
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
