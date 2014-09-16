<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file AbstractEntity.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\AbstractEntities;

use RZ\Renzo\Core\AbstractEntities\PersistableInterface;

/**
 * Base entity implementing PersistableInterface to offer a unique Id.
 *
 * @MappedSuperclass
 */
abstract class AbstractEntity implements PersistableInterface
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    private $id;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
}
