<?php
namespace RZ\Renzo\Core\AbstractEntities;

use RZ\Renzo\Core\AbstractEntities\DateTimed;
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