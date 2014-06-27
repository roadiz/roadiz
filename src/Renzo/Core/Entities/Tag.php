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
	private $value;
	/**
	 * @return [type] [description]
	 */
	public function getValue() {
	    return $this->value;
	}
	/**
	 * @param [type] $newnodeName [description]
	 */
	public function setValue($value) {
	    $this->value = $value;
	
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
	 * Value types
	 * Use NodeTypeField types constants
	 * 
	 * @Column(type="boolean")
	 */
	private $type = true;
	/**
	 * @return [type] [description]
	 */
	public function getType() {
	    return $this->type;
	}
	/**
	 * @param [type] $newvisible [description]
	 */
	public function setType($type) {
	    $this->type = (int)$type;
	
	    return $this;
	}
	

	/**
	 * @param NodeType $nodeType [description]
	 */
	public function __construct()
    {
    	parent::__construct();
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