<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file Tag.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Entities;

use RZ\Renzo\Core\Handlers\TagHandler;
use RZ\Renzo\Core\Utils\StringHandler;
use RZ\Renzo\Core\AbstractEntities\AbstractDateTimedPositioned;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Tags are hierarchical entities used
 * to qualify Nodes, Documents, Subscribers.
 *
 * @Entity(repositoryClass="RZ\Renzo\Core\Repositories\TagRepository")
 * @Table(name="tags", indexes={
 *     @index(name="visible_tag_idx", columns={"visible"}),
 *     @index(name="locked_tag_idx", columns={"locked"}),
 *     @index(name="position_tag_idx", columns={"position"})
 * })
 */
class Tag extends AbstractDateTimedPositioned
{
    /**
     * @Column(type="string", name="tag_name", unique=true)
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
     * @Column(type="boolean")
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
     * @Column(type="boolean")
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
     * @ManyToMany(targetEntity="Node", mappedBy="tags")
     * @JoinTable(name="nodes_tags")
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
     * @ManyToMany(targetEntity="Subscriber", mappedBy="tags")
     * @JoinTable(name="subscribers_tags")
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
     * @ManyToMany(targetEntity="Document", mappedBy="tags")
     * @JoinTable(name="documents_tags")
     * @var ArrayCollection
     */
    private $documents = null;
    /**
     * @return ArrayCollection
     */
    public function getDocuments()
    {
        return $this->documents;
    }

    /**
     * @ManyToOne(targetEntity="Tag", inversedBy="children", fetch="EXTRA_LAZY")
     * @JoinColumn(name="parent_tag_id", referencedColumnName="id", onDelete="CASCADE")
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
     * @OneToMany(targetEntity="Tag", mappedBy="parent", orphanRemoval=true, fetch="EXTRA_LAZY")
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
     * @OneToMany(targetEntity="TagTranslation", mappedBy="tag", orphanRemoval=true, fetch="EXTRA_LAZY")
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

        $this->nodes = new ArrayCollection();
        $this->subscribers = new ArrayCollection();
        $this->documents = new ArrayCollection();
        $this->translatedTags = new ArrayCollection();
        $this->children = new ArrayCollection();
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
     * @return RZ\Renzo\Core\Handlers\TagHandler
     */
    public function getHandler()
    {
        return new TagHandler($this);
    }
}
