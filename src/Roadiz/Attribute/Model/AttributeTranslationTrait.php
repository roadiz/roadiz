<?php
declare(strict_types=1);

namespace RZ\Roadiz\Attribute\Model;

use JMS\Serializer\Annotation as Serializer;
use Doctrine\ORM\Mapping as ORM;
use RZ\Roadiz\Core\AbstractEntities\TranslationInterface;

trait AttributeTranslationTrait
{
    /**
     * @var TranslationInterface|null
     * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Core\AbstractEntities\TranslationInterface")
     * @ORM\JoinColumn(onDelete="CASCADE")
     * @Serializer\Groups({"attribute", "node", "nodes_sources"})
     * @Serializer\Type("RZ\Roadiz\Core\AbstractEntities\TranslationInterface")
     * @Serializer\Accessor(getter="getTranslation", setter="setTranslation")
     */
    protected ?TranslationInterface $translation = null;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=false, unique=false)
     * @Serializer\Groups({"attribute", "node", "nodes_sources"})
     * @Serializer\Type("string")
     */
    protected string $label = '';

    /**
     * @var array|null
     * @ORM\Column(type="simple_array", nullable=true, unique=false)
     * @Serializer\Groups({"attribute"})
     * @Serializer\Type("array")
     */
    protected ?array $options = [];

    /**
     * @var AttributeInterface|null
     * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Attribute\Model\AttributeInterface", inversedBy="attributeTranslations", cascade={"persist"})
     * @ORM\JoinColumn(onDelete="CASCADE", referencedColumnName="id")
     * @Serializer\Exclude
     */
    protected ?AttributeInterface $attribute = null;

    /**
     * @return string
     */
    public function getLabel(): ?string
    {
        return $this->label;
    }

    /**
     * @param string|null $label
     *
     * @return mixed
     */
    public function setLabel(?string $label)
    {
        $this->label = null !== $label ? trim($label) : null;
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
