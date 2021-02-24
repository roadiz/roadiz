<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use RZ\Roadiz\Attribute\Model\AttributableInterface;
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
     * @var Node|null
     * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Core\Entities\Node", inversedBy="attributeValues")
     * @ORM\JoinColumn(name="node_id", onDelete="CASCADE")
     * @Serializer\Exclude
     */
    protected ?Node $node = null;

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
        if (null === $attributable || $attributable instanceof Node) {
            $this->node = $attributable;
            return $this;
        }
        throw new \InvalidArgumentException('Attributable have to be an instance of Node.');
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
