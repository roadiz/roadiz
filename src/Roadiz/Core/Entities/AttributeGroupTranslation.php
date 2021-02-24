<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Entities;

use Doctrine\ORM\Mapping as ORM;
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
}
