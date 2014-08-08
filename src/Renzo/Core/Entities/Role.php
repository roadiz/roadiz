<?php 

namespace RZ\Renzo\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\AbstractEntities\PersistableObject;

/**
 * @Entity(repositoryClass="RZ\Renzo\Core\Utils\EntityRepository")
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
	public function getClassName()
	{
		return str_replace('_', '-', strtolower($this->getName()));
	}

	public function required()
	{
		if ($this->getName() == static::ROLE_DEFAULT ||
			$this->getName() == static::ROLE_SUPER_ADMIN ||
			$this->getName() == static::ROLE_BACKEND_USER) {
			return true;
		}
		return false;
	}
	
	/**
	 * @param string $name Role name
	 */
	public function __construct( $name )
    {
    	parent::__construct();

    	$this->setName($name);
    }
}