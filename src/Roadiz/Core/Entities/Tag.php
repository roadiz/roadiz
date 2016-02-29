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
 * @file Tag.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use RZ\Roadiz\Core\AbstractEntities\AbstractDateTimedPositioned;
use RZ\Roadiz\Core\Handlers\TagHandler;
use RZ\Roadiz\Utils\StringHandler;

/**
 * Tags are hierarchical entities used
 * to qualify Nodes.
 *
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\TagRepository")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="tags", indexes={
 *     @ORM\Index(columns={"visible"}),
 *     @ORM\Index(columns={"locked"}),
 *     @ORM\Index(columns={"position"}),
 *     @ORM\Index(columns={"created_at"}),
 *     @ORM\Index(columns={"updated_at"})
 * })
 */
class Tag extends AbstractDateTimedPositioned
{
    /**
     * @ORM\Column(type="string", name="tag_name", unique=true)
     */
    private $tagName;
    /**
     * @return string
     */
    public function getTagName()
    {
        return $this->tagName;
    }
    /**
     * @param string $tagName
     *
     * @return $this
     */
    public function setTagName($tagName)
    {
        $this->dirtyTagName = $tagName;
        $this->tagName = StringHandler::slugify($tagName);

        return $this;
    }

    private $dirtyTagName;

    /**
     * Gets the value of dirtyTagName.
     *
     * @return string
     */
    public function getDirtyTagName()
    {
        return $this->dirtyTagName;
    }

    /**
     * @ORM\Column(type="string", name="color", length=7, unique=false, nullable=false, options={"default" = "#000000"})
     */
    protected $color = '#000000';

    /**
     * @ORM\Column(type="boolean", nullable=false, options={"default" = true})
     */
    private $visible = true;

    /**
     * @ORM\Column(type="string", name="children_order", options={"default" = "position"})
     */
    private $childrenOrder = 'position';

    /**
     * @ORM\Column(type="string", name="children_order_direction", length=4, options={"default" = "ASC"})
     */
    private $childrenOrderDirection = 'ASC';

    /**
     * @return boolean
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
        $this->visible = (boolean) $visible;

        return $this;
    }

    /**
     * @ORM\Column(type="boolean", nullable=false, options={"default" = false})
     */
    private $locked = false;
    /**
     * @return boolean
     */
    public function isLocked()
    {
        return $this->locked;
    }
    /**
     * @param boolean $locked
     *
     * @return $this
     */
    public function setLocked($locked)
    {
        $this->locked = (boolean) $locked;

        return $this;
    }

    /**
     * @ORM\ManyToMany(targetEntity="Node", mappedBy="tags")
     * @ORM\JoinTable(name="nodes_tags")
     * @var ArrayCollection
     */
    private $nodes = null;
    /**
     * @return ArrayCollection
     */
    public function getNodes()
    {
        return $this->nodes;
    }

    /**
     * @ORM\ManyToOne(targetEntity="Tag", inversedBy="children", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="parent_tag_id", referencedColumnName="id", onDelete="CASCADE")
     * @var Tag
     */
    private $parent;

    /**
     * @return Tag parent
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param Tag $parent
     *
     * @return $this
     */
    public function setParent(Tag $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @ORM\OneToMany(targetEntity="Tag", mappedBy="parent", orphanRemoval=true)
     * @ORM\OrderBy({"position" = "ASC"})
     * @var ArrayCollection
     */
    private $children;

    /**
     * @return ArrayCollection
     */
    public function getChildren()
    {
        return $this->children;
    }
    /**
     * @param Tag $child
     *
     * @return $this
     */
    public function addChild(Tag $child)
    {
        if (!$this->getChildren()->contains($child)) {
            $this->getChildren()->add($child);
        }

        return $this;
    }
    /**
     * @param Tag $child
     *
     * @return $this
     */
    public function removeChild(Tag $child)
    {
        if ($this->getChildren()->contains($child)) {
            $this->getChildren()->removeElement($child);
        }

        return $this;
    }

    /**
     * @ORM\OneToMany(targetEntity="TagTranslation", mappedBy="tag", orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var ArrayCollection
     */
    private $translatedTags = null;
    /**
     * @return ArrayCollection
     */
    public function getTranslatedTags()
    {
        return $this->translatedTags;
    }
    /**
     * Create a new Tag.
     */
    public function __construct()
    {
        $this->nodes = new ArrayCollection();
        $this->translatedTags = new ArrayCollection();
        $this->children = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function getOneLineSummary()
    {
        return $this->getId() . " — " . $this->getTagName() .
            " — Visible : " . ($this->isVisible() ? 'true' : 'false') . PHP_EOL;
    }

    /**
     * @return \RZ\Roadiz\Core\Handlers\TagHandler
     */
    public function getHandler()
    {
        return new TagHandler($this);
    }

    /**
     * Gets the value of color.
     *
     * @return string
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * Sets the value of color.
     *
     * @param string $color the color
     *
     * @return self
     */
    public function setColor($color)
    {
        $this->color = $color;

        return $this;
    }

    /**
     * Gets the value of childrenOrder.
     *
     * @return mixed
     */
    public function getChildrenOrder()
    {
        return $this->childrenOrder;
    }

    /**
     * Sets the value of childrenOrder.
     *
     * @param mixed $childrenOrder the children order
     *
     * @return self
     */
    public function setChildrenOrder($childrenOrder)
    {
        $this->childrenOrder = $childrenOrder;

        return $this;
    }

    /**
     * Gets the value of childrenOrderDirection.
     *
     * @return mixed
     */
    public function getChildrenOrderDirection()
    {
        return $this->childrenOrderDirection;
    }

    /**
     * Sets the value of childrenOrderDirection.
     *
     * @param mixed $childrenOrderDirection the children order direction
     *
     * @return self
     */
    public function setChildrenOrderDirection($childrenOrderDirection)
    {
        $this->childrenOrderDirection = $childrenOrderDirection;

        return $this;
    }
}
