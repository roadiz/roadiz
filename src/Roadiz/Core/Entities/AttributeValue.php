<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use RZ\Roadiz\Attribute\Model\AttributableInterface;
use RZ\Roadiz\Attribute\Model\AttributeInterface;
use RZ\Roadiz\Attribute\Model\AttributeValueInterface;
use RZ\Roadiz\Attribute\Model\AttributeValueTrait;
use RZ\Roadiz\Attribute\Model\AttributeValueTranslationInterface;
use RZ\Roadiz\Core\AbstractEntities\AbstractPositioned;

/**
 * @package RZ\Roadiz\Core\Entities
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Attribute\Repository\AttributeValueRepository")
 * @ORM\Table(name="attribute_values", indexes={
 *     @ORM\Index(columns={"attribute_id", "node_id"})
 * })
 * @ORM\HasLifecycleCallbacks
 */
class AttributeValue extends AbstractPositioned implements AttributeValueInterface
{
    use AttributeValueTrait;

    /**
     * @var Attribute|null
     * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Core\Entities\Attribute", inversedBy="attributeValues", fetch="EAGER")
     * @ORM\JoinColumn(name="attribute_id", onDelete="CASCADE", referencedColumnName="id")
     * @Serializer\Groups({"attribute", "node", "nodes_sources"})
     * @Serializer\Type("RZ\Roadiz\Core\Entities\Attribute")
     */
    protected $attribute;

    /**
     * @var Collection<AttributeValueTranslation>
     * @ORM\OneToMany(
     *     targetEntity="RZ\Roadiz\Core\Entities\AttributeValueTranslation",
     *     mappedBy="attributeValue",
     *     fetch="EAGER",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     * @Serializer\Groups({"attribute", "node", "nodes_sources"})
     * @Serializer\Type("ArrayCollection<RZ\Roadiz\Core\Entities\AttributeValueTranslation>")
     * @Serializer\Accessor(getter="getAttributeValueTranslations",setter="setAttributeValueTranslations")
     */
    protected $attributeValueTranslations;

    /**
     * @var Node|null
     * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Core\Entities\Node", inversedBy="attributeValues")
     * @ORM\JoinColumn(name="node_id", onDelete="CASCADE")
     * @Serializer\Exclude
     */
    protected $node;

    /**
     * AttributeValue constructor.
     */
    public function __construct()
    {
        $this->attributeValueTranslations = new ArrayCollection();
    }

    /**
     * @inheritDoc
     */
    public function getAttributable(): ?AttributableInterface
    {
        return $this->node;
    }

    /**
     * @inheritDoc
     */
    public function setAttributable(?AttributableInterface $attributable)
    {
        if ($attributable instanceof Node) {
            $this->node = $attributable;
            return $this;
        }
        throw new \InvalidArgumentException('Attributable have to be an instance of Node.');
    }

    /**
     * @inheritDoc
     */
    public function setAttribute(AttributeInterface $attribute)
    {
        if ($attribute instanceof Attribute) {
            $this->attribute = $attribute;
        }
        return $this;
    }

    /**
     * @return Node|null
     */
    public function getNode(): ?Node
    {
        return $this->node;
    }

    /**
     * @param Node|null $node
     *
     * @return AttributeValue
     */
    public function setNode(?Node $node): AttributeValue
    {
        $this->node = $node;

        return $this;
    }

    /**
     * After clone method.
     *
     * Clone current node and ist relations.
     */
    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
            $attributeValueTranslations = $this->getAttributeValueTranslations();
            if ($attributeValueTranslations !== null) {
                $this->attributeValueTranslations = new ArrayCollection();
                /** @var AttributeValueTranslationInterface $attributeValueTranslation */
                foreach ($attributeValueTranslations as $attributeValueTranslation) {
                    $cloneAttributeValueTranslation = clone $attributeValueTranslation;
                    $cloneAttributeValueTranslation->setAttributeValue($this);
                    $this->attributeValueTranslations->add($cloneAttributeValueTranslation);
                }
            }
        }
    }
}
