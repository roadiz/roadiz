<?php
namespace RZ\Renzo\Core\AbstractEntities;

use RZ\Renzo\Core\AbstractEntities\DateTimed;
/**
 * @MappedSuperclass
 * @HasLifecycleCallbacks
 * @Table(indexes={@Index(name="position_idx", columns={"position"})})
 */
abstract class DateTimedPositioned extends DateTimed
{
	/**
	 * @Column(type="float")
	 */
	private $position = 0;
	public function getPosition() {
	    return $this->position;
	}

	/**
	 * Set position as a float to enable increment and decrement by O.5 
	 * to insert a node between two others.
	 * 
	 * @param float $newPosition
	 */
	public function setPosition($newPosition)
	{	
		$this->position = (float)$newPosition;
		return $this;
	}

	public function __construct()
	{
		parent::__construct();
	}
}