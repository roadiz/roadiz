<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use RZ\Roadiz\Attribute\Model\AttributeGroupInterface;
use RZ\Roadiz\Attribute\Model\AttributeInterface;
use RZ\Roadiz\Attribute\Model\AttributeTrait;
use RZ\Roadiz\Attribute\Model\AttributeTranslationInterface;
use RZ\Roadiz\Attribute\Model\AttributeValueInterface;
use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;
use JMS\Serializer\Annotation as Serializer;

/**
 * @package RZ\Roadiz\Core\Entities
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\EntityRepository")
 * @ORM\Table(name="attributes", indexes={
 *     @ORM\Index(columns={"code"}),
 *     @ORM\Index(columns={"type"}),
 *     @ORM\Index(columns={"searchable"}),
 *     @ORM\Index(columns={"group_id"})
 * })
 * @ORM\HasLifecycleCallbacks
 */
class Attribute extends AbstractEntity implements AttributeInterface
{
    use AttributeTrait;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=false, unique=true)
     * @Serializer\Groups({"attribute", "node", "nodes_sources"})
     * @Serializer\Type("string")
     */
    protected $code = '';

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false, unique=false, options={"default" = false})
     * @Serializer\Groups({"attribute"})
     * @Serializer\Type("boolean")
     */
    protected $searchable = false;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=false, unique=false)
     * @Serializer\Groups({"attribute"})
     * @Serializer\Type("integer")
     */
    protected $type = AttributeInterface::STRING_T;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=7, nullable=true, unique=false)
     * @Serializer\Groups({"attribute", "node", "nodes_sources"})
     * @Serializer\Type("string")
     */
    protected $color = null;

    /**
     * @var AttributeGroupInterface|null
     * @ORM\ManyToOne(
     *     targetEntity="RZ\Roadiz\Core\Entities\AttributeGroup",
     *     inversedBy="attributes",
     *     fetch="EAGER",
     *     cascade={"persist", "merge"}
     * )
     * @ORM\JoinColumn(name="group_id", onDelete="SET NULL")
     * @Serializer\Groups({"attribute", "node", "nodes_sources"})
     * @Serializer\Type("RZ\Roadiz\Core\Entities\AttributeGroup")
     */
    protected $group = null;

    /**
     * @var Collection<AttributeTranslationInterface>
     * @ORM\OneToMany(
     *     targetEntity="RZ\Roadiz\Core\Entities\AttributeTranslation",
     *     mappedBy="attribute",
     *     fetch="EAGER",
     *     cascade={"all"},
     *     orphanRemoval=true
     * )
     * @Serializer\Groups({"attribute", "node", "nodes_sources"})
     * @Serializer\Type("ArrayCollection<RZ\Roadiz\Core\Entities\AttributeTranslation>")
     * @Serializer\Accessor(getter="getAttributeTranslations",setter="setAttributeTranslations")
     */
    protected $attributeTranslations;

    /**
     * @var Collection<AttributeValueInterface>
     * @ORM\OneToMany(
     *     targetEntity="RZ\Roadiz\Core\Entities\AttributeValue",
     *     mappedBy="attribute",
     *     fetch="EXTRA_LAZY",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     * @Serializer\Exclude
     */
    protected $attributeValues;

    /**
     * @ORM\OneToMany(
     *     targetEntity="RZ\Roadiz\Core\Entities\AttributeDocuments",
     *     mappedBy="attribute",
     *     orphanRemoval=true,
     *     cascade={"persist", "merge"}
     * )
     * @ORM\OrderBy({"position" = "ASC"})
     * @var Collection<AttributeDocuments>|null
     * @Serializer\Exclude
     * @Serializer\Type("ArrayCollection<RZ\Roadiz\Core\Entities\AttributeDocuments>")
     */
    protected $attributeDocuments = null;

    /**
     * Attribute constructor.
     */
    public function __construct()
    {
        $this->attributeTranslations = new ArrayCollection();
        $this->attributeValues = new ArrayCollection();
        $this->attributeDocuments = new ArrayCollection();
    }

    /**
     * @return ArrayCollection|null
     */
    public function getAttributeDocuments(): ?Collection
    {
        return $this->attributeDocuments;
    }

    /**
     * @param Collection|null $attributeDocuments
     *
     * @return Attribute
     */
    public function setAttributeDocuments(?Collection $attributeDocuments): Attribute
    {
        $this->attributeDocuments = $attributeDocuments;

        return $this;
    }

    /**
     * @return Collection
     * @Serializer\SerializedName("documents")
     * @Serializer\VirtualProperty()
     * @Serializer\Groups({"attribute", "node", "nodes_sources"})
     */
    public function getDocuments(): Collection
    {
        return $this->attributeDocuments->map(function (AttributeDocuments $attributeDocuments) {
            return $attributeDocuments->getDocument();
        });
    }
}
