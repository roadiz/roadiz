<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use RZ\Roadiz\Attribute\Model\AttributeGroupInterface;
use RZ\Roadiz\Attribute\Model\AttributeGroupTrait;
use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;

/**
 * @package RZ\Roadiz\Core\Entities
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\EntityRepository")
 * @ORM\Table(name="attribute_groups", indexes={
 *     @ORM\Index(columns={"name"}),
 *     @ORM\Index(columns={"canonical_name"})
 * })
 * @ORM\HasLifecycleCallbacks
 */
class AttributeGroup extends AbstractEntity implements AttributeGroupInterface
{
    use AttributeGroupTrait;

    /**
     * @var string|null
     * @ORM\Column(type="string", nullable=false, unique=false)
     * @Serializer\Groups({"attribute_group", "attribute", "node", "nodes_sources"})
     * @Serializer\Type("string")
     */
    protected $name;

    /**
     * @var string|null
     * @ORM\Column(type="string", name="canonical_name", nullable=false, unique=true)
     * @Serializer\Groups({"attribute_group", "attribute", "node", "nodes_sources"})
     * @Serializer\Type("string")
     */
    protected $canonicalName;

    /**
     * @var Collection
     * @ORM\OneToMany(targetEntity="RZ\Roadiz\Core\Entities\Attribute", mappedBy="group")
     * @Serializer\Groups({"attribute_group"})
     * @Serializer\Type("Collection<RZ\Roadiz\Core\Entities\Attribute>")
     */
    protected $attributes;

    /**
     * AttributeGroup constructor.
     */
    public function __construct()
    {
        $this->attributes = new ArrayCollection();
    }
}
