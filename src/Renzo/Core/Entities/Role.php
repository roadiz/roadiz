<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file Role.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\AbstractEntities\AbstractEntity;

/**
 * Roles are persisted version of string Symfony's roles.
 *
 * @Entity(repositoryClass="RZ\Renzo\Core\Utils\EntityRepository")
 * @Table(name="roles")
 */
class Role extends AbstractEntity
{
    const ROLE_DEFAULT =      'ROLE_USER';
    const ROLE_SUPER_ADMIN =  'ROLE_SUPER_ADMIN';
    const ROLE_BACKEND_USER = 'ROLE_BACKEND_USER';

    /**
     * @Column(type="string", unique=true)
     * @var string
     */
    private $name;
    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }
    /**
     * Get a classified version of current role name.
     *
     * It replace underscores by dashes and lowercase.
     *
     * @return string
     */
    public function getClassName()
    {
        return str_replace('_', '-', strtolower($this->getName()));
    }
    /**
     * @return boolean
     */
    public function required()
    {
        if ($this->getName() == static::ROLE_DEFAULT ||
            $this->getName() == static::ROLE_SUPER_ADMIN ||
            $this->getName() == static::ROLE_BACKEND_USER) {
            return true;
        }

        return false;
    }

    /**
     * Create a new Role with its string representation.
     *
     * @param string $name Role name
     */
    public function __construct($name)
    {
        parent::__construct();

        $this->setName($name);
    }
}