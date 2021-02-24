<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Entities;

use Doctrine\ORM\Mapping as ORM;
use RZ\Roadiz\Attribute\Model\AttributeValueTranslationInterface;
use RZ\Roadiz\Attribute\Model\AttributeValueTranslationTrait;
use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;

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
}
