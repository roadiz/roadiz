<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file Group.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\AbstractEntities\AbstractEntity;
use RZ\Renzo\Core\Handlers\GroupHandler;

/**
 * A group gather User and Roles.
 *
 * @Entity(repositoryClass="RZ\Renzo\Core\Utils\EntityRepository")
 * @Table(name="groups")
 */
class Group extends AbstractEntity
{
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
     * @ManyToMany(targetEntity="RZ\Renzo\Core\Entities\User", mappedBy="groups")
     * @var ArrayCollection
     */
    private $users;

    /**
     * @return ArrayCollection
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * @ManyToMany(targetEntity="RZ\Renzo\Core\Entities\Role", inversedBy="groups")
     * @JoinTable(name="groups_roles",
     *      joinColumns={@JoinColumn(name="group_id", referencedColumnName="id")},
     *      inverseJoinColumns={@JoinColumn(name="role_id", referencedColumnName="id")}
     * )
     * @var ArrayCollection
     */
    private $roles;
    private $rolesNames = null;

    /**
     * Get roles entities.
     *
     * @return ArrayCollection
     */
    public function getRolesEntities()
    {
        return $this->roles;
    }
    /**
     * Get roles names as a simple array.
     *
     * @return array
     */
    public function getRoles()
    {
        if ($this->rolesNames === null) {
            $this->rolesNames = array();
            foreach ($this->getRolesEntities() as $role) {
                $this->rolesNames[] = $role->getName();
            }
        }

        return $this->rolesNames;
    }
    /**
     * @param RZ\Renzo\Core\Entities\Role $role
     *
     * @return $this
     */
    public function addRole(Role $role)
    {
        if (!$this->getRolesEntities()->contains($role)) {
            $this->getRolesEntities()->add($role);
        }

        return $this;
    }
    /**
     * @param RZ\Renzo\Core\Entities\Role $role
     *
     * @return $this
     */
    public function removeRole(Role $role)
    {
        if ($this->getRolesEntities()->contains($role)) {
            $this->getRolesEntities()->removeElement($role);
        }

        return $this;
    }

    /**
     * @return GroupHandler
     */
    public function getHandler()
    {
        return new GroupHandler($this);
    }

    /**
     * Create a new Group.
     */
    public function __construct()
    {
        $this->roles = new ArrayCollection();
        $this->users = new ArrayCollection();
        $this->rolesNames = null;
    }
}
