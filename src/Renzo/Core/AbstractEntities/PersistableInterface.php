<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 *
 * @file PersistableInterface.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */

namespace RZ\Renzo\Core\AbstractEntities;

/**
 * Base entity interface which deals with identifier.
 *
 * Every database entity should implements that interface.
 */
interface PersistableInterface
{
    /**
     * Get entity unique identifier.
     *
     * @return int
     */
    public function getId();
}
