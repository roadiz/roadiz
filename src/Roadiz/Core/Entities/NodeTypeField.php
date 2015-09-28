<?php
/**
 * Copyright © 2014, Ambroise Maupate and Julien Blanchet
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
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file NodeTypeField.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Entities;

use Doctrine\ORM\Mapping as ORM;
use RZ\Roadiz\Core\AbstractEntities\AbstractField;
use RZ\Roadiz\Core\Handlers\NodeTypeFieldHandler;

/**
 * NodeTypeField entities are used to create NodeTypes with
 * custom data structure.
 *
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\NodeTypeFieldRepository")
 * @ORM\Table(name="node_type_fields",  indexes={
 *         @ORM\Index(columns={"visible"}),
 *         @ORM\Index(columns={"indexed"}),
 *         @ORM\Index(columns={"position"}),
 *         @ORM\Index(columns={"group_name"}),
 *         @ORM\Index(columns={"type"})
 *     },
 *     uniqueConstraints={@ORM\UniqueConstraint(columns={"name", "node_type_id"})}
 * )
 * @ORM\HasLifecycleCallbacks
 */
class NodeTypeField extends AbstractField
{
    /**
     * @ORM\ManyToOne(targetEntity="NodeType", inversedBy="fields")
     * @ORM\JoinColumn(name="node_type_id", onDelete="CASCADE")
     */
    private $nodeType;

    /**
     * @return RZ\Roadiz\Core\Entities\NodeType
     */
    public function getNodeType()
    {
        return $this->nodeType;
    }

    /**
     * @param RZ\Roadiz\Core\Entities\NodeType $nodeType
     *
     * @return $this
     */
    public function setNodeType($nodeType)
    {
        $this->nodeType = $nodeType;

        return $this;
    }

    /**
     * @ORM\Column(name="min_length", type="integer", nullable=true)
     */
    private $minLength = null;

    /**
     * @return int
     */
    public function getMinLength()
    {
        return $this->minLength;
    }

    /**
     * @param int $minValue
     *
     * @return $this
     */
    public function setMinLength($minLength)
    {
        $this->minLength = $minLength;

        return $this;
    }

    /**
     * @ORM\Column(name="max_length", type="integer", nullable=true)
     */
    private $maxLength = null;

    /**
     * @return int
     */
    public function getMaxLength()
    {
        return $this->maxLength;
    }

    /**
     * @param int $maxLength
     *
     * @return $this
     */
    public function setMaxLength($maxLength)
    {
        $this->maxLength = $maxLength;

        return $this;
    }

    /**
     * @ORM\Column(type="boolean", nullable=false, options={"default" = false})
     */
    private $indexed = false;

    /**
     * @return boolean $isIndexed
     */
    public function isIndexed()
    {
        return $this->indexed;
    }

    /**
     * @param boolean $indexed
     *
     * @return $this
     */
    public function setIndexed($indexed)
    {
        $this->indexed = $indexed;

        return $this;
    }

    /**
     * @ORM\Column(type="boolean", nullable=false, options={"default" = true})
     */
    private $visible = true;

    /**
     * @return boolean $isVisible
     */
    public function isVisible()
    {
        return $this->visible;
    }

    /**
     * @param boolean $visible
     *
     * @return $this
     */
    public function setVisible($visible)
    {
        $this->visible = $visible;

        return $this;
    }

    /**
     * @ORM\Column(name="group_name", type="string", nullable=true)
     */
    private $groupName;

    /**
     * @return RZ\Roadiz\Core\Handlers\NodeTypeFieldHandler
     */
    public function getHandler()
    {
        return new NodeTypeFieldHandler($this);
    }

    /**
     * Tell if current field can be searched and indexed in a Search engine server.
     *
     * @return boolean
     */
    public function isSearchable()
    {
        return (boolean) in_array($this->getType(), static::$searchableTypes);
    }

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        /*
         * Get the last index after last node in parent
         */
        $this->setPosition($this->getHandler()->cleanPositions());
    }

    /**
     * @return string
     */
    public function getOneLineSummary()
    {
        return $this->getId() . " — " . $this->getName() . " — " . $this->getLabel() .
        " — Indexed : " . ($this->isIndexed() ? 'true' : 'false') . PHP_EOL;
    }

    /**
     * Gets the value of groupName.
     *
     * @return string
     */
    public function getGroupName()
    {
        return $this->groupName;
    }

    /**
     * Sets the value of groupName.
     *
     * @param string $groupName the group name
     *
     * @return self
     */
    public function setGroupName($groupName)
    {
        $this->groupName = trim(strip_tags($groupName));

        return $this;
    }
}
