<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file AbstractDateTimedPositioned.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\AbstractEntities;

use RZ\Renzo\Core\AbstractEntities\AbstractDateTimed;
/**
 * Combined AbstractDateTimed and AbstractPositioned.
 *
 * @MappedSuperclass
 * @HasLifecycleCallbacks
 * @Table(indexes={@Index(name="position_idx", columns={"position"})})
 */
abstract class AbstractDateTimedPositioned extends AbstractDateTimed
{
    /**
     * @Column(type="float")
     */
    private $position = 0;
    /**
     * @return integer
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Set position as a float to enable increment and decrement by O.5
     * to insert a node between two others.
     *
     * @param float $newPosition
     *
     * @return $this
     */
    public function setPosition($newPosition)
    {
        $this->position = (float) $newPosition;

        return $this;
    }
}
