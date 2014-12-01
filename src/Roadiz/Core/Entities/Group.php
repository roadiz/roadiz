<?php
/**
 * Copyright © 2014, REZO ZERO
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the REZO ZERO shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from the REZO ZERO SARL.
 *
 * @file Group.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;
use RZ\Roadiz\Core\Handlers\GroupHandler;
use Doctrine\ORM\Mapping AS ORM;

/**
 * A group gather User and Roles.
 *
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\EntityRepository")
 * @ORM\Table(name="groups")
 */
class Group extends AbstractEntity
{
    /**
     * @ORM\Column(type="string", unique=true)
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
     * @ORM\ManyToMany(targetEntity="RZ\Roadiz\Core\Entities\User", mappedBy="groups")
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
     * @ORM\ManyToMany(targetEntity="RZ\Roadiz\Core\Entities\Role", inversedBy="groups")
     * @ORM\JoinTable(name="groups_roles",
     *      joinColumns={@ORM\JoinColumn(name="group_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="role_id", referencedColumnName="id")}
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
     * @param RZ\Roadiz\Core\Entities\Role $role
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
     * @param RZ\Roadiz\Core\Entities\Role $role
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
