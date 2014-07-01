<?php 

namespace RZ\Renzo\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\AbstractEntities\PersistableObject;

/**
 * @Entity
 * @Table(name="documents")
 */
class Document extends PersistableObject
{
	/**
	 * @Column(name="relative_url", type="string", unique=true, nullable=false)
	 */
	private $relativeUrl;
	/**
	 * @return
	 */
	public function getRelativeUrl() {
	    return $this->relativeUrl;
	}
	/**
	 * @param $newrelativeUrl
	 */
	public function setRelativeUrl($relativeUrl) {
	    $this->relativeUrl = $relativeUrl;
	    return $this;
	}

	/**
	 * @Column(type="string", nullable=true)
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
	 * @Column(type="text", nullable=true)
	 */
	private $description;
	public function getDescription() {
	    return $this->description;
	}
	public function setDescription($description) {
	    $this->description = $description;
	
	    return $this;
	}
	
	/**
	 * @Column(type="boolean")
	 */
	private $visible = true;
	/**
	 * @return [type] [description]
	 */
	public function isVisible() {
	    return $this->visible;
	}
	/**
	 * @param [type] $newvisible [description]
	 */
	public function setVisible($visible) {
	    $this->visible = (boolean)$visible;
	
	    return $this;
	}

	/**
     * @ManyToMany(targetEntity="Tag", inversedBy="documents")
     * @JoinTable(name="documents_tags")
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
	 * @param NodeType $nodeType [description]
	 */
	public function __construct()
    {
    	parent::__construct();
    	
        $this->tags = new ArrayCollection();
    }

    public function getOneLineSummary()
	{
		return $this->getId()." — ".$this->getName()." — ".$this->getNodeType()->getName().
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
}