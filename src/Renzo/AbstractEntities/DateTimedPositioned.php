<?php
namespace RZ\Renzo\AbstractEntities;


use RZ\Renzo\AbstractEntities\DateTimed;
/**
* @MappedSuperclass
*/
abstract class DateTimedPositioned extends DateTimed
{
	/**
	 * @Column(type="integer")
	 */
	private $position = 0;
	public function getPosition() {
	    return $this->position;
	}
}