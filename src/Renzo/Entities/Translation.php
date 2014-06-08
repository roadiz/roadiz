<?php 


namespace RZ\Renzo\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\AbstractEntities\DateTimed;

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
	 * @Column(type="string", unique=true)
	 */
	private $name;

	/**
	 * @Column(type="boolean")
	 */
	private $available = true;
}