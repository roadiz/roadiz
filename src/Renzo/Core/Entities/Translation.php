<?php 


namespace RZ\Renzo\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\AbstractEntities\DateTimed;

/**
 * @Entity
 */
class Translation extends DateTimed {

	/**
	 * Language locale
	 * 
	 * fr_FR or en_US for example
	 * 
	 * @Column(type="string", unique=true, length=10)
	 */
	private $locale;

	/**
	 * @return [type] [description]
	 */
	public function getLocale() {
	    return $this->locale;
	}
	
	/**
	 * @param [type] $newlocale [description]
	 */
	public function setLocale($locale) {
	    $this->locale = $locale;
	
	    return $this;
	}

	/**
	 * @Column(type="string", unique=true)
	 */
	private $name;

	/**
	 * @return [type] [description]
	 */
	public function getName() {
	    return $this->name;
	}
	
	/**
	 * @param [type] $newname [description]
	 */
	public function setName($name) {
	    $this->name = $name;
	
	    return $this;
	}

	/**
	 * @Column(type="boolean")
	 */
	private $available = true;

	/**
	 * @return [type] [description]
	 */
	public function isAvailable() {
	    return $this->available;
	}
	
	/**
	 * @param [type] $newavailable [description]
	 */
	public function setAvailable($available) {
	    $this->available = $available;
	
	    return $this;
	}


	public function getOneLineSummary()
	{
		return $this->getId()." — ".$this->getName()." — ".$this->getLocale().
			" — Available : ".($this->isAvailable()?'true':'false').PHP_EOL;
	}
}