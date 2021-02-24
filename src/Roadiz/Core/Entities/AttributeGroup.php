<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
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
