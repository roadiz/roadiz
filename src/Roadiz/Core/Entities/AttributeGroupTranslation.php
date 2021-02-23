<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Entities;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use RZ\Roadiz\Attribute\Model\AttributeGroupInterface;
use RZ\Roadiz\Attribute\Model\AttributeGroupTranslationInterface;
use RZ\Roadiz\Attribute\Model\AttributeGroupTranslationTrait;
use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;

/**
 * @package RZ\Roadiz\Core\Entities
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Attribute\Repository\AttributeGroupTranslationRepository")
 * @ORM\Table(name="attribute_group_translations", indexes={
 *     @ORM\Index(columns={"name"})
 * }, uniqueConstraints={
 *     @ORM\UniqueConstraint(columns={"attribute_group_id", "translation_id"}),
 *     @ORM\UniqueConstraint(columns={"name", "translation_id"})
 * }))
 * @ORM\HasLifecycleCallbacks
 */
class AttributeGroupTranslation extends AbstractEntity implements AttributeGroupTranslationInterface
{
    use AttributeGroupTranslationTrait;

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
     * @var Translation|null
     * @ORM\ManyToOne(targetEntity="\RZ\Roadiz\Core\Entities\Translation")
     * @ORM\JoinColumn(onDelete="CASCADE", name="translation_id")
     * @Serializer\Groups({"attribute_group", "attribute", "node", "nodes_sources"})
     * @Serializer\Type("RZ\Roadiz\Core\Entities\Translation")
     * @Serializer\Accessor(getter="getTranslation", setter="setTranslation")
     */
    protected ?Translation $translation = null;

    /**
     * @param AttributeGroupInterface $attributeGroup
     *
     * @return $this|mixed
     */
    public function setAttributeGroup(AttributeGroupInterface $attributeGroup)
    {
        if ($attributeGroup instanceof AttributeGroup) {
            $this->attributeGroup = $attributeGroup;
        }
        return $this;
    }
}
