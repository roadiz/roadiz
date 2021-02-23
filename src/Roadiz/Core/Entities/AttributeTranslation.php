<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Entities;

use Doctrine\ORM\Mapping as ORM;
use RZ\Roadiz\Attribute\Model\AttributeInterface;
use RZ\Roadiz\Attribute\Model\AttributeTranslationInterface;
use RZ\Roadiz\Attribute\Model\AttributeTranslationTrait;
use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;
use JMS\Serializer\Annotation as Serializer;

/**
 * @package RZ\Roadiz\Core\Entities
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\EntityRepository")
 * @ORM\Table(name="attribute_translations", indexes={
 *     @ORM\Index(columns={"label"})
 * }, uniqueConstraints={
 *     @ORM\UniqueConstraint(columns={"attribute_id", "translation_id"})
 * }))
 * @ORM\HasLifecycleCallbacks
 */
class AttributeTranslation extends AbstractEntity implements AttributeTranslationInterface
{
    use AttributeTranslationTrait;

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
     * @var Attribute|null
     * @ORM\ManyToOne(targetEntity="\RZ\Roadiz\Core\Entities\Attribute", inversedBy="attributeTranslations", cascade={"persist"})
     * @ORM\JoinColumn(onDelete="CASCADE", referencedColumnName="id")
     * @Serializer\Exclude
     */
    protected ?Attribute $attribute = null;

    /**
     * @var Translation|null
     * @ORM\ManyToOne(targetEntity="\RZ\Roadiz\Core\Entities\Translation")
     * @ORM\JoinColumn(onDelete="CASCADE")
     * @Serializer\Groups({"attribute", "node", "nodes_sources"})
     * @Serializer\Type("RZ\Roadiz\Core\Entities\Translation")
     * @Serializer\Accessor(getter="getTranslation", setter="setTranslation")
     */
    protected ?Translation $translation = null;

    /**
     * @param AttributeInterface $attribute
     *
     * @return $this|mixed
     */
    public function setAttribute(AttributeInterface $attribute)
    {
        if ($attribute instanceof Attribute) {
            $this->attribute = $attribute;
        }
        return $this;
    }
}
