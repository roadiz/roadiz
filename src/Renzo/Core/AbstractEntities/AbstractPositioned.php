<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file AbstractPositioned.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\AbstractEntities;

/**
 * Abstract class which describe a positioned entity
 *
 * @MappedSuperclass
 */
abstract class AbstractPositioned
{
    /**
     * @Column(type="float")
     */
    private $position = 0.0;

    /**
     * @return float
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @param float $newPosition
     *
     * @return $this
     */
    public function setPosition($newPosition)
    {
        if ($newPosition > -1) {
            $this->position = (float) $newPosition;
        }

        return $this;
    }
}
