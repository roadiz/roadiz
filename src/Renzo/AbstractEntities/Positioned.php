<?php
namespace RZ\Renzo\AbstractEntities;

/**
* @MappedSuperclass
*/
abstract class Positioned
{
	/**
	 * @Column(type="integer")
	 */
	private $position = 0;
	public function getPosition() {
	    return $this->position;
	}
}