<?php 

namespace RZ\Renzo\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\AbstractEntities\PersistableObject;

/**
 * @Entity
 * @Table(name="newsletters")
 */
class Newsletter extends PersistableObject
{
	
	public function __construct()
    {
    	parent::__construct();
    }
}