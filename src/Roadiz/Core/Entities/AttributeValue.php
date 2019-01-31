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
 * @file AttributeValue.php
 * @author Ambroise Maupate
 *
 */
declare(strict_types=1);

namespace RZ\Roadiz\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use RZ\Roadiz\Attribute\Model\AttributableInterface;
use RZ\Roadiz\Attribute\Model\AttributeInterface;
use RZ\Roadiz\Attribute\Model\AttributeValueInterface;
use RZ\Roadiz\Attribute\Model\AttributeValueTrait;
use RZ\Roadiz\Attribute\Model\AttributeValueTranslationInterface;
use RZ\Roadiz\Core\AbstractEntities\AbstractPositioned;

/**
 * @package RZ\Roadiz\Core\Entities
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\EntityRepository")
 * @ORM\Table(name="attribute_values")
 * @ORM\HasLifecycleCallbacks
 */
class AttributeValue extends AbstractPositioned implements AttributeValueInterface
{
    use AttributeValueTrait;

    /**
     * @var AttributeInterface
     * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Core\Entities\Attribute", inversedBy="attributeValues")
     */
    protected $attribute;

    /**
     * @var Collection<AttributeValueTranslationInterface>
 *     @ORM\OneToMany(targetEntity="RZ\Roadiz\Core\Entities\AttributeValueTranslation", mappedBy="attributeValue", fetch="EAGER", cascade={"persist", "remove"})
     */
    protected $attributeValueTranslations;

    /**
     * @var Node|null
     * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Core\Entities\Node", inversedBy="attributeValues")
     */
    protected $node;

    /**
     * AttributeValue constructor.
     */
    public function __construct()
    {
        $this->attributeValueTranslations = new ArrayCollection();
    }

    /**
     * @inheritDoc
     */
    public function getAttributable(): ?AttributableInterface
    {
        return $this->node;
    }

    /**
     * @inheritDoc
     */
    public function setAttributable(?AttributableInterface $attributable)
    {
        if ($attributable instanceof Node) {
            $this->node = $attributable;
            return $this;
        }
        throw new \InvalidArgumentException('Attributable have to be an instance of Node.');
    }
}
