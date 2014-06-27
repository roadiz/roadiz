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
	
	public function __construct()
    {
    	parent::__construct();
    }
}