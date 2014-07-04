<?php
namespace RZ\Renzo\Core\AbstractEntities;

use RZ\Renzo\Core\AbstractEntities\DateTimed;
/**
 * @MappedSuperclass
 * @Table(indexes={@Index(name="position_idx", columns={"position"})})
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

	public function setPosition($newPosition)
	{	
		if ($newPosition > -1) {
			$this->position = (int)$newPosition;
		}
		return $this;
	}

	public function __construct()
	{
		parent::__construct();
	}
}