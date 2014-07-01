<?php 

namespace RZ\Renzo\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\AbstractEntities\PersistableObject;
use RZ\Renzo\Core\Utils\StringHandler;
use RZ\Renzo\Core\Entities\NodeTypeField;

/**
 * @Entity(repositoryClass="RZ\Renzo\Core\Entities\SettingRepository")
 * @Table(name="settings")
 */
class Setting extends PersistableObject
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

		$this->name = trim(strtolower($name));
		$this->name = StringHandler::removeDiacritics($this->name);
	    $this->name = preg_replace('#([^a-z])#', '_', $this->name);
	
	    return $this;
	}

	/**
	 * @Column(type="text", nullable=true)
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
	 * @Column(type="integer")
	 */
	private $type = NodeTypeField::STRING_T;
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
}