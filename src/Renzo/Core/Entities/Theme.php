<?php 

namespace RZ\Renzo\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\AbstractEntities\PersistableObject;

/**
 * @Entity
 * @Table(name="themes", indexes={
 * 		@index(name="backend_idx", columns={"backend"}),
 * 		@index(name="available_idx", columns={"available"})
 * })
 */
class Theme extends PersistableObject {

	/**
	 * @Column(type="boolean")
	 * @var boolean
	 */
	private $available = false;
	/**
	 * @return boolean [description]
	 */
	public function isAvailable() {
	    return $this->available;
	}
	
	/**
	 * @param boolean $newavailable
	 */
	public function setAvailable($available) {
	    $this->available = $available;
	
	    return $this;
	}

	/**
	 * @Column(name="classname", type="string", unique=true)
	 * @var string
	 */
	private $className;

	/**
	 * @return string
	 */
	public function getClassName() {
	    return $this->className;
	}
	
	/**
	 * @param string $newclassName
	 */
	public function setClassName($className) {
	    $this->className = $className;
	
	    return $this;
	}

	/**
	 * @Column(name="hostname",type="string")
	 * @var string
	 */
	private $hostname = '*';

	/**
	 * @return string
	 */
	public function getHostname() {
	    return $this->hostname;
	}
	
	/**
	 * @param string $newhostname
	 */
	public function setHostname($hostname) {
	    $this->hostname = $hostname;
	
	    return $this;
	}

	/**
	 * @Column(name="backend", type="boolean")
	 * @var boolean
	 */
	private $backendTheme = false;

	/**
	 * @return boolean
	 */
	public function isBackendTheme() {
	    return $this->backendTheme;
	}
	
	/**
	 * @param boolean $newbackendTheme
	 */
	public function setBackendTheme($backendTheme) {
	    $this->backendTheme = $backendTheme;
	
	    return $this;
	}
}