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
 * @file AttributeValueTranslation.php
 * @author Ambroise Maupate
 *
 */
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
     * @var string|null
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
