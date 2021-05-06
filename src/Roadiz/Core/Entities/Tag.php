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
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use RZ\Roadiz\Core\AbstractEntities\AbstractDateTimedPositioned;
use RZ\Roadiz\Core\AbstractEntities\LeafInterface;
use RZ\Roadiz\Core\AbstractEntities\LeafTrait;
use RZ\Roadiz\Utils\StringHandler;
use JMS\Serializer\Annotation as Serializer;

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
 *     @ORM\Index(columns={"updated_at"}),
 *     @ORM\Index(columns={"visible", "position"}, name="tag_visible_position"),
 *     @ORM\Index(columns={"parent_tag_id", "visible", "position"}, name="tag_parent_visible_position")
 * })
 */
class Tag extends AbstractDateTimedPositioned implements LeafInterface
{
    use LeafTrait;

    /**
     * @var string
     * @ORM\Column(type="string", name="color", length=7, unique=false, nullable=false, options={"default" = "#000000"})
     * @Serializer\Groups({"tag", "color"})
     * @Serializer\Type("string")
     */
    protected $color = '#000000';
    /**
     * @ORM\ManyToOne(targetEntity="Tag", inversedBy="children", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="parent_tag_id", referencedColumnName="id", onDelete="CASCADE")
     * @var Tag
     * @Serializer\Exclude
     */
    protected $parent;
    /**
     * @ORM\OneToMany(targetEntity="Tag", mappedBy="parent", orphanRemoval=true, cascade={"persist", "merge"})
     * @ORM\OrderBy({"position" = "ASC"})
     * @var ArrayCollection
     * @Serializer\Groups({"tag"})
     * @Serializer\Type("ArrayCollection<RZ\Roadiz\Core\Entities\Tag>")
     * @Serializer\Accessor(setter="setChildren", getter="getChildren")
     */
    protected $children;
    /**
     * @ORM\OneToMany(
     *     targetEntity="TagTranslation",
     *     mappedBy="tag",
     *     fetch="EAGER",
     *     orphanRemoval=true,
     *     cascade={"all"}
     * )
     * @var ArrayCollection
     * @Serializer\Groups({"tag", "node", "nodes_sources"})
     * @Serializer\Type("ArrayCollection<RZ\Roadiz\Core\Entities\TagTranslation>")
     * @Serializer\Accessor(setter="setTranslatedTags", getter="getTranslatedTags")
     */
    protected $translatedTags = null;
    /**
     * @var string
     * @ORM\Column(type="string", name="tag_name", unique=true)
     * @Serializer\Groups({"tag", "node", "nodes_sources"})
     * @Serializer\Type("string")
     * @Serializer\Accessor(getter="getTagName", setter="setTagName")
     */
    private $tagName;
    /**
     * @var string
     * @Serializer\Exclude
     */
    private $dirtyTagName;
    /**
     * @ORM\Column(type="boolean", nullable=false, options={"default" = true})
     * @Serializer\Groups({"tag", "node", "nodes_sources"})
     * @Serializer\Type("bool")
     */
    private $visible = true;

    /**
     * @ORM\Column(type="string", name="children_order", options={"default" = "position"})
     * @Serializer\Groups({"tag"})
     * @Serializer\Type("string")
     */
    private $childrenOrder = 'position';

    /**
     * @ORM\Column(type="string", name="children_order_direction", length=4, options={"default" = "ASC"})
     * @Serializer\Groups({"tag"})
     * @Serializer\Type("string")
     */
    private $childrenOrderDirection = 'ASC';
    /**
     * @ORM\Column(type="boolean", nullable=false, options={"default" = false})
     * @Serializer\Groups({"tag"})
     * @Serializer\Type("bool")
     */
    private $locked = false;
    /**
     * @ORM\ManyToMany(targetEntity="Node", mappedBy="tags")
     * @ORM\JoinTable(name="nodes_tags")
     * @var ArrayCollection
     * @Serializer\Exclude
     */
    private $nodes = null;

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
     * Gets the value of dirtyTagName.
     *
     * @return string
     */
    public function getDirtyTagName()
    {
        return $this->dirtyTagName;
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
     * @return ArrayCollection
     */
    public function getNodes()
    {
        return $this->nodes;
    }

    /**
     * Get tag full path using tag names.
     *
     * @return string
     */
    public function getFullPath()
    {
        $parents = $this->getParents();
        $path = [];

        /** @var Tag $parent */
        foreach ($parents as $parent) {
            $path[] = $parent->getTagName();
        }

        $path[] = $this->getTagName();

        return implode('/', $path);
    }

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

    /**
     * @return ArrayCollection
     */
    public function getTranslatedTags(): Collection
    {
        return $this->translatedTags;
    }

    /**
     * @param Collection $translatedTags
     *
     * @return Tag
     */
    public function setTranslatedTags(Collection $translatedTags): self
    {
        $this->translatedTags = $translatedTags;
        /** @var TagTranslation $translatedTag */
        foreach ($this->translatedTags as $translatedTag) {
            $translatedTag->setTag($this);
        }
        return $this;
    }

    /**
     * @param Translation $translation
     * @return Collection
     */
    public function getTranslatedTagsByTranslation(Translation $translation)
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('translation', $translation));

        return $this->translatedTags->matching($criteria);
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
     * @return boolean
     */
    public function isVisible()
    {
        return $this->visible;
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

    /**
     * @inheritDoc
     */
    public function __toString()
    {
        return '[' . ($this->getId() > 0 ? $this->getId() : 'NULL') . '] ' . $this->getTagName();
    }
}
