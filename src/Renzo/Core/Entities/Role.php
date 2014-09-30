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
use RZ\Renzo\Core\Utils\StringHandler;

/**
 * Roles are persisted version of string Symfony's roles.
 *
 * @Entity(repositoryClass="RZ\Renzo\Core\Repositories\RoleRepository")
 * @Table(name="roles")
 */
class Role extends AbstractEntity implements \Symfony\Component\Security\Core\Role\RoleInterface
{
    const ROLE_DEFAULT =      'ROLE_USER';
    const ROLE_SUPERADMIN =   'ROLE_SUPERADMIN';
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
     * {@inheritdoc}
     *
     * @return string
     */
    public function getRole()
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
        $this->name = static::cleanName($name);

        return $this;
    }

    /**
     * @param string $name
     *
     * @return string $name
     */
    public static function cleanName($name)
    {
        $name = StringHandler::variablize($name);

        if (0 === preg_match("/^role_/i", $name)) {
            $name = "ROLE_" . $name;
        }

        return strtoupper($name);
    }

    /**
     * @ManyToMany(targetEntity="RZ\Renzo\Core\Entities\Group", mappedBy="roles")
     *
     * @var ArrayCollection
     */
    private $groups;

    /**
     * @return Doctrine\Common\Collections\ArrayCollection
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * @param RZ\Renzo\Core\Entities\Group $group
     *
     * @return RZ\Renzo\Core\Entities\Group
     */
    public function addGroup(Group $group)
    {
        if (!$this->getGroups()->contains($group)) {
            $this->getGroups()->add($group);
        }

        return $this;
    }

    /**
     * @param RZ\Renzo\Core\Entities\Group $group
     *
     * @return RZ\Renzo\Core\Entities\Group
     */
    public function removeGroup(Group $group)
    {
        if ($this->getGroups()->contains($group)) {
            $this->getGroups()->removeElement($group);
        }

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
            $this->getName() == static::ROLE_SUPERADMIN ||
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
    public function __construct($name = null)
    {
        $this->setName($name);
    }
}
