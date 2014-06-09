<?php 

namespace RZ\Renzo\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\AbstractEntities\DateTimedPositioned;

/**
 * @Entity
 */
class Node extends DateTimedPositioned
{
	
	/**
	 * @Column(type="string", name="node_name", unique=true)
	 */
	private $nodeName;
	/**
	 * @return [type] [description]
	 */
	public function getNodeName() {
	    return $this->nodeName;
	}
	/**
	 * @param [type] $newnodeName [description]
	 */
	public function setNodeName($nodeName) {
	    $this->nodeName = $nodeName;
	
	    return $this;
	}
	
	/**
	 * @Column(type="boolean")
	 */
	private $visible = true;

	/**
	 * [description here]
	 *
	 * @return [type] [description]
	 */
	public function isVisible() {
	    return $this->visible;
	}
	
	/**
	 * [Description]
	 *
	 * @param [type] $newvisible [description]
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
	 * @return [type] [description]
	 */
	public function isPublished() {
	    return $this->published;
	}
	
	/**
	 * @param [type] $newpublished [description]
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
	 * @return [type] [description]
	 */
	public function isLocked() {
	    return $this->locked;
	}
	
	/**
	 * @param [type] $newlocked [description]
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
	 * @return [type] [description]
	 */
	public function isArchived() {
	    return $this->archived;
	}
	/**
	 * @param [type] $newarchived [description]
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
	 * @return [type] [description]
	 */
	public function getNodeType() {
	    return $this->nodeType;
	}
	
	/**
	 * @param [type] $newnodeType [description]
	 */
	public function setNodeType($nodeType) {
	    $this->nodeType = $nodeType;
	
	    return $this;
	}

	/**
	 * @ManyToOne(targetEntity="Node", fetch="EXTRA_LAZY")
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
	 * @OneToMany(targetEntity="Node", mappedBy="parent", orphanRemoval=true, fetch="EXTRA_LAZY")
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
	 * @param [type] $newchildren [description]
	 */
	public function addChild($child) {
	    $this->children[] = $child;
	
	    return $this;
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
	 * Node source according to its node-type
	 * @var PersistableObject
	 */
	private $source;
	/**
	 * @return PersistableObject
	 */
	public function getSource() {
	    return $this->source;
	}
	public function setSource( $source) {
	    $this->source = $source;
	    return $this;
	}


	/**
	 * @param NodeType $nodeType [description]
	 */
	public function __construct( NodeType $nodeType )
    {
    	parent::__construct();

        $this->childrens = new ArrayCollection();
        $this->nodeSources = new ArrayCollection();
        $this->setNodeType($nodeType);
    }

    public function getOneLineSummary()
	{
		return $this->getId()." — ".$this->getNodeName()." — ".$this->getNodeType()->getName().
			" — Visible : ".($this->isVisible()?'true':'false').PHP_EOL;
	}
}