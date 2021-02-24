<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Entities;

use Doctrine\ORM\Mapping as ORM;
use RZ\Roadiz\Attribute\Model\AttributeTranslationInterface;
use RZ\Roadiz\Attribute\Model\AttributeTranslationTrait;
use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;

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
}
