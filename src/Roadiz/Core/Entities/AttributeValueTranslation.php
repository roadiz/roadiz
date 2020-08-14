<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Entities;

use Doctrine\ORM\Mapping as ORM;
use RZ\Roadiz\Attribute\Model\AttributeValueInterface;
use RZ\Roadiz\Attribute\Model\AttributeValueTranslationInterface;
use RZ\Roadiz\Attribute\Model\AttributeValueTranslationTrait;
use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;
use JMS\Serializer\Annotation as Serializer;

/**
 * @package RZ\Roadiz\Core\Entities
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\EntityRepository")
 * @ORM\Table(name="attribute_value_translations", indexes={
 *     @ORM\Index(columns={"value"}),
 *     @ORM\Index(columns={"translation_id", "attribute_value"})
 * })
 * @ORM\HasLifecycleCallbacks
 */
class AttributeValueTranslation extends AbstractEntity implements AttributeValueTranslationInterface
{
    use AttributeValueTranslationTrait;

    /**
     * @var string|float|int|bool|null
     * @ORM\Column(type="string", nullable=true, unique=false, length=255)
     * @Serializer\Groups({"attribute", "node", "nodes_sources"})
     * @Serializer\Type("string")
     */
    protected $value;

    /**
     * @var Translation
     * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Core\Entities\Translation")
     * @ORM\JoinColumn(name="translation_id", onDelete="CASCADE", referencedColumnName="id")
     * @Serializer\Groups({"attribute", "node", "nodes_sources"})
     * @Serializer\Type("RZ\Roadiz\Core\Entities\Translation")
     * @Serializer\Accessor(getter="getTranslation", setter="setTranslation")
     */
    protected $translation;

    /**
     * @var AttributeValueInterface
     * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Core\Entities\AttributeValue", inversedBy="attributeValueTranslations", cascade={"persist"})
     * @ORM\JoinColumn(name="attribute_value", onDelete="CASCADE", referencedColumnName="id")
     * @Serializer\Exclude
     */
    protected $attributeValue;
}
