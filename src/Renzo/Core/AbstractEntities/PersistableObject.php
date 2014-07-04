<?php 

namespace RZ\Renzo\Core\AbstractEntities;

use RZ\Renzo\Core\AbstractEntities\Persistable;
/**
* @MappedSuperclass
*/
abstract class PersistableObject implements Persistable
{
	/**
	 * @Id
	 * @Column(type="integer")
	 * @GeneratedValue
	 */
	private $id;
	public function getId()
	{
		return $this->id;
	}

	public function __construct(){
		
	}
}