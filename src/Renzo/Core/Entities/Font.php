<?php 

namespace RZ\Renzo\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\AbstractEntities\PersistableObject;

/**
 * @Entity(repositoryClass="RZ\Renzo\Core\Utils\EntityRepository")
 * @Table(name="fonts")
 */
class Font extends PersistableObject { 
	/**
	 * @Column(type="string", unique=true)
	 * @var string
	 */
	private $name;
	/**
	 * @return string
	 */
	public function getName() {
	    return $this->name;
	}

	/**
	 * @param string $newname
	 */
	public function setName($name) {
	    $this->name = $name;
	
	    return $this;
	}
	
	/**
	 * @param string $name Font name
	 */
	public function __construct($name) {
    	parent::__construct();

    	$this->setName($name);
    }
}