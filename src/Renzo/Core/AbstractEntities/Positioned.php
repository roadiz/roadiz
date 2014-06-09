<?php
namespace RZ\Renzo\Core\AbstractEntities;

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

	public function setPosition($newPosition)
	{	
		if ($newPosition > -1) {
			$this->position = (int)$newPosition;
		}
		return $this;
	}
}