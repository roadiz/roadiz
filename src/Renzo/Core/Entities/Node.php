<?php 

namespace RZ\Renzo\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use RZ\Renzo\Core\AbstractEntities\DateTimedPositioned;

use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Core\Utils\StringHandler;
use RZ\Renzo\Core\Entities\NodesSources;
use RZ\Renzo\Core\Handlers\NodeHandler;

/**
 * @Entity(repositoryClass="RZ\Renzo\Core\Entities\NodeRepository")
 * @Table(name="nodes", indexes={
 *     @index(name="visible_idx",   columns={"visible"}), 
 *     @index(name="published_idx", columns={"published"}), 
 *     @index(name="locked_idx",    columns={"locked"}), 
 *     @index(name="archived_idx",  columns={"archived"}),
 *     @index(name="position_idx", columns={"position"})
 * })
 * @HasLifecycleCallbacks
 */
class Node extends DateTimedPositioned
{
	
	/**
	 * @Column(type="string", name="node_name", unique=true)
	 */
	private $nodeName;
	/**
	 * @return string
	 */
	public function getNodeName() {
	    return $this->nodeName;
	}
	/**
	 * @param string $newnodeName
	 */
	public function setNodeName($nodeName) {
		$this->nodeName = StringHandler::slugify($nodeName);
	
	    return $this;
	}

	/**
	 * @Column(type="boolean", name="home")
	 */
	private $home = false;
	/**
	 * @return boolean
	 */
	public function isHome() {
	    return (boolean)$this->home;
	}
	/**
	 * @param boolean $home
	 */
	public function setHome($home) {
		$this->home = (boolean)$home;
	    return $this;
	}
	
	/**
	 * @Column(type="boolean")
	 */
	private $visible = true;

	/**
	 * @return boolean
	 */
	public function isVisible() {
	    return $this->visible;
	}
	
	/**
	 * @param boolean $newvisible
	 */
	public function setVisible($visible) {
	    $this->visible = $visible;
	
	    return $this;
	}
	/**
	 * @Column(type="boolean")
	 */
	private $published = false;

	/**
	 * @return boolean
	 */
	public function isPublished() {
	    return $this->published;
	}
	
	/**
	 * @param boolean $newpublished
	 */
	public function setPublished($published) {
	    $this->published = $published;
	
	    return $this;
	}
	/**
	 * @Column(type="boolean")
	 */
	private $locked = false;

	/**
	 * @return boolean
	 */
	public function isLocked() {
	    return $this->locked;
	}
	
	/**
	 * @param boolean $newlocked
	 */
	public function setLocked($locked) {
	    $this->locked = $locked;
	
	    return $this;
	}
	/**
	 * @Column(type="boolean")
	 */
	private $archived = false;
	/**
	 * @return boolean
	 */
	public function isArchived() {
	    return $this->archived;
	}
	/**
	 * @param boolean $newarchived
	 */
	public function setArchived($archived) {
	    $this->archived = $archived;
	
	    return $this;
	}

	/**
	 * @Column(type="string", name="children_order")
	 */
	private $childrenOrder = 'order';

	/**
	 * @return [type] [description]
	 */
	public function getChildrenOrder() {
	    return $this->childrenOrder;
	}
	
	/**
	 * @param [type] $newchildrenOrder [description]
	 */
	public function setChildrenOrder($childrenOrder) {
	    $this->childrenOrder = $childrenOrder;
	
	    return $this;
	}
	/**
	 * @Column(type="string", name="children_order_direction", length=4)
	 */
	private $childrenOrderDirection = 'ASC';

	/**
	 * @return [type] [description]
	 */
	public function getChildrenOrderDirection() {
	    return $this->childrenOrderDirection;
	}
	
	/**
	 * @param [type] $newchildrenOrderDirection [description]
	 */
	public function setChildrenOrderDirection($childrenOrderDirection) {
	    $this->childrenOrderDirection = $childrenOrderDirection;
	
	    return $this;
	}

	/**
	 * @ManyToOne(targetEntity="NodeType")
	 * @var NodeType
	 */
	private $nodeType;

	/**
	 * @return NodeType
	 */
	public function getNodeType() {
	    return $this->nodeType;
	}
	
	/**
	 * @param NodeType $newnodeType
	 */
	public function setNodeType($nodeType) {
	    $this->nodeType = $nodeType;
	
	    return $this;
	}

	/**
	 * @ManyToOne(targetEntity="RZ\Renzo\Core\Entities\Node", inversedBy="children", fetch="EXTRA_LAZY")
	 * @JoinColumn(name="parent_node_id", referencedColumnName="id", onDelete="CASCADE")
	 * @var Node
	 */
	private $parent;

	/**
	 * @return Node parent
	 */
	public function getParent() {
	    return $this->parent;
	}
	
	/**
	 * @param Node $newparent [description]
	 */
	public function setParent($parent) {
	    $this->parent = $parent;
	
	    return $this;
	}

	/**
	 * @OneToMany(targetEntity="RZ\Renzo\Core\Entities\Node", mappedBy="parent", orphanRemoval=true, fetch="EXTRA_LAZY")
	 * @OrderBy({"position" = "ASC"})
	 * @var ArrayCollection
	 */
	private $children;

	/**
	 * @return ArrayCollection
	 */
	public function getChildren() {
	    return $this->children;
	}
	/**
	 * @param Node $newchildren
	 * @return Node
	 */
	public function addChild( Node $child ) {
	    $this->children[] = $child;
	    return $this;
	}
	/**
	 * @param  Node   $child 
	 * @return Node
	 */
	public function removeChild( Node $child ) {
        $this->children->removeElement($child);
	    return $this;
    }

    /**
     * @ManyToMany(targetEntity="Tag", inversedBy="nodes", orphanRemoval=true, fetch="EXTRA_LAZY")
     * @JoinTable(name="nodes_tags")
     * @var ArrayCollection
     */
    private $tags = null;
    /**
     * @return ArrayCollection
     */
    public function getTags() {
        return $this->tags;
    }

	/**
	 * @OneToMany(targetEntity="NodesSources", mappedBy="node", orphanRemoval=true, fetch="EXTRA_LAZY")
	 */
	private $nodeSources;

	/**
	 * @return ArrayCollection
	 */
	public function getNodeSources() {
	    return $this->nodeSources;
	}

	/**
	 * @param NodeType $nodeType [description]
	 */
	public function __construct( NodeType $nodeType = null )
    {
    	parent::__construct();

        $this->tags = new ArrayCollection();
        $this->childrens = new ArrayCollection();
        $this->nodeSources = new ArrayCollection();
        $this->setNodeType($nodeType);
    }

    public function getOneLineSummary()
	{
		return $this->getId()." — ".$this->getNodeName()." — ".$this->getNodeType()->getName().
			" — Visible : ".($this->isVisible()?'true':'false').PHP_EOL;
	}

	public function getOneLineSourceSummary()
	{
		$text = "Source ".$this->getDefaultNodeSource()->getId().PHP_EOL;

		foreach ($this->getNodeType()->getFields() as $key => $field) {
			$getterName = 'get'.ucwords($field->getName());
			$text .= '['.$field->getLabel().']: '.$this->getDefaultNodeSource()->$getterName().PHP_EOL;
		}
		return $text;
	}

	/**
	 * @PrePersist
	 * 
	 * @return void
	 */
	public function prePersist()
	{
		parent::prePersist();

		/*
		 * Get the last index after last node in parent
		 */
		$this->setPosition($this->getHandler()->cleanPositions());
	}

	/**
	 * 
	 * @return NodeTypeHandler
	 */
	public function getHandler()
	{
		return new NodeHandler( $this );
	}
}