<?php
/**
 * Copyright © 2014, REZO ZERO
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
 * Except as contained in this notice, the name of the REZO ZERO shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from the REZO ZERO SARL.
 *
 * @file Tag.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Entities;

use RZ\Roadiz\Core\Handlers\TagHandler;
use RZ\Roadiz\Core\Utils\StringHandler;
use RZ\Roadiz\Core\AbstractEntities\AbstractDateTimedPositioned;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Tags are hierarchical entities used
 * to qualify Nodes, Documents, Subscribers.
 *
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\TagRepository")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="tags", indexes={
 *     @ORM\Index(name="visible_tag_idx",  columns={"visible"}),
 *     @ORM\Index(name="locked_tag_idx",   columns={"locked"}),
 *     @ORM\Index(name="position_tag_idx", columns={"position"})
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
        $this->tagName = StringHandler::slugify($tagName);

        return $this;
    }

    /**
     * @ORM\Column(type="boolean")
     */
    private $visible = true;
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
     * @ORM\Column(type="boolean")
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
     * @ORM\ManyToMany(targetEntity="Subscriber", mappedBy="tags")
     * @ORM\JoinTable(name="subscribers_tags")
     * @var ArrayCollection
     */
    private $subscribers = null;
    /**
     * @return ArrayCollection
     */
    public function getSubscribers()
    {
        return $this->subscribers;
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
    public function setParent($parent)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @ORM\OneToMany(targetEntity="Tag", mappedBy="parent", orphanRemoval=true, fetch="EXTRA_LAZY")
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
        //$this->setTagName('Tag '.uniqid());

        $this->nodes =          new ArrayCollection();
        $this->subscribers =    new ArrayCollection();
        $this->documents =      new ArrayCollection();
        $this->translatedTags = new ArrayCollection();
        $this->children =       new ArrayCollection();
    }

    /**
     * @todo Move this method to a TagViewer
     * @return string
     */
    public function getOneLineSummary()
    {
        return $this->getId()." — ".$this->getName()." — ".$this->getNodeType()->getName().
            " — Visible : ".($this->isVisible()?'true':'false').PHP_EOL;
    }

    /**
     * @return RZ\Roadiz\Core\Handlers\TagHandler
     */
    public function getHandler()
    {
        return new TagHandler($this);
    }

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        parent::prePersist();

        /*
         * If no plain password is present, we must generate one
         */
        if ($this->getTranslatedTags()->count() === 0) {
            throw new \Exception("Cannot create a tag without a tag-translation", 1);
        }
    }
}
