<?php 

namespace RZ\Renzo\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\AbstractEntities\PersistableObject;

/**
 * @Entity
 * @Table(name="tags")
 */
class Tag extends PersistableObject
{
	
	/**
	 * @Column(type="string", unique=true)
	 */
	private $name;
	/**
	 * @return
	 */
	public function getName() {
	    return $this->name;
	}
	/**
	 * @param $newnodeName 
	 */
	public function setName($name) {
	    $this->name = $name;
	
	    return $this;
	}

	/**
	 * @Column(type="text")
	 */
	private $description;
	/**
	 * @return string
	 */
	public function getDescription() {
	    return $this->description;
	}
	/**
	 * @param string $newnodeName
	 */
	public function setDescription($description) {
	    $this->description = $description;
	
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
	    $this->visible = (boolean)$visible;
	
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
    public function getNodes() {
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
    public function getSubscribers() {
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
    public function getDocuments() {
        return $this->documents;
    }
	
	/**
	 * @ManyToOne(targetEntity="Tag", fetch="EXTRA_LAZY")
	 * @var Node
	 */
	private $parent;

	/**
	 * @return Tag parent
	 */
	public function getParent() {
	    return $this->parent;
	}
	
	/**
	 * @param Tag $newparent [description]
	 */
	public function setParent($parent) {
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
	public function getChildren() {
	    return $this->children;
	}
	/**
	 * @param Tag $newchildren
	 * @return Tag
	 */
	public function addChild( Tag $child ) {
	    $this->children[] = $child;
	    return $this;
	}
	/**
	 * @param  Tag   $child 
	 * @return Tag
	 */
	public function removeChild( Tag $child ) {
        $this->children->removeElement($child);
	    return $this;
    }

	/**
	 * @param NodeType $nodeType [description]
	 */
	public function __construct()
    {
    	parent::__construct();

    	$this->nodes = new ArrayCollection();
    	$this->subscribers = new ArrayCollection();
    	$this->documents = new ArrayCollection();
    }

    public function getOneLineSummary()
	{
		return $this->getId()." — ".$this->getName()." — ".$this->getNodeType()->getName().
			" — Visible : ".($this->isVisible()?'true':'false').PHP_EOL;
	}
}