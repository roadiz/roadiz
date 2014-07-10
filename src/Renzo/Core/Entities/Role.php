<?php 

namespace RZ\Renzo\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\AbstractEntities\PersistableObject;

/**
 * @Entity
 * @Table(name="roles")
 */
class Role extends PersistableObject
{
	const ROLE_DEFAULT =      'ROLE_USER';
	const ROLE_SUPER_ADMIN =  'ROLE_SUPER_ADMIN';
	const ROLE_BACKEND_USER = 'ROLE_BACKEND_USER';
 
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
	
	public function __construct()
    {
    	parent::__construct();
    }
}