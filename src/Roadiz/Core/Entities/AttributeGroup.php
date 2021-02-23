<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use RZ\Roadiz\Attribute\Model\AttributeGroupInterface;
use RZ\Roadiz\Attribute\Model\AttributeGroupTrait;
use RZ\Roadiz\Attribute\Model\AttributeGroupTranslationInterface;
use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;

/**
 * @package RZ\Roadiz\Core\Entities
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\EntityRepository")
 * @ORM\Table(name="attribute_groups", indexes={
 *     @ORM\Index(columns={"canonical_name"})
 * })
 * @ORM\HasLifecycleCallbacks
 */
class AttributeGroup extends AbstractEntity implements AttributeGroupInterface
{
    use AttributeGroupTrait;

    /**
     * @var string
     * @ORM\Column(type="string", name="canonical_name", nullable=false, unique=true)
     * @Serializer\Groups({"attribute_group", "attribute", "node", "nodes_sources"})
     * @Serializer\Type("string")
     */
    protected string $canonicalName = '';

    /**
     * @var Collection<Attribute>
     * @ORM\OneToMany(targetEntity="RZ\Roadiz\Core\Entities\Attribute", mappedBy="group")
     * @Serializer\Groups({"attribute_group"})
     * @Serializer\Type("ArrayCollection<RZ\Roadiz\Core\Entities\Attribute>")
     */
    protected Collection $attributes;

    /**
     * @var Collection<AttributeGroupTranslation>
     * @ORM\OneToMany(targetEntity="\RZ\Roadiz\Core\Entities\AttributeGroupTranslation", mappedBy="attributeGroup", cascade={"all"})
     * @Serializer\Groups({"attribute_group", "attribute", "node", "nodes_sources"})
     * @Serializer\Type("ArrayCollection<RZ\Roadiz\Core\Entities\AttributeGroupTranslation>")
     * @Serializer\Accessor(getter="getAttributeGroupTranslations", setter="setAttributeGroupTranslations")
     */
    protected $attributeGroupTranslations;

    public function __construct()
    {
        $this->attributes = new ArrayCollection();
        $this->attributeGroupTranslations = new ArrayCollection();
    }

    protected function createAttributeGroupTranslation(): AttributeGroupTranslationInterface
    {
        return (new AttributeGroupTranslation())->setAttributeGroup($this);
    }
}
