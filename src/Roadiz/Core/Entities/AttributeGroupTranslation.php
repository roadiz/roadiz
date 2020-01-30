<?php
/**
 * Copyright © 2019, Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the roadiz shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file AttributeTranslation.php
 * @author Ambroise Maupate
 *
 */
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
    protected $name;

    /**
     * @var AttributeGroupInterface
     * @ORM\ManyToOne(targetEntity="\RZ\Roadiz\Core\Entities\AttributeGroup", inversedBy="attributeGroupTranslations", cascade={"persist"})
     * @ORM\JoinColumn(onDelete="CASCADE", referencedColumnName="id", name="attribute_group_id")
     * @Serializer\Exclude
     */
    protected $attributeGroup;

    /**
     * @var Translation|null
     * @ORM\ManyToOne(targetEntity="\RZ\Roadiz\Core\Entities\Translation")
     * @ORM\JoinColumn(onDelete="CASCADE", name="translation_id")
     * @Serializer\Groups({"attribute_group", "attribute", "node", "nodes_sources"})
     * @Serializer\Type("RZ\Roadiz\Core\Entities\Translation")
     * @Serializer\Accessor(getter="getTranslation", setter="setTranslation")
     */
    protected $translation;
}
