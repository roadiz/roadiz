<?php 

namespace RZ\Renzo\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\AbstractEntities\PersistableObject;

/**
 * @Entity(repositoryClass="RZ\Renzo\Core\Utils\EntityRepository")
 * @Table(name="groups")
 */
class Group extends PersistableObject
{ 
	/**
	 * @Column(type="string", unique=true)
	 * @var string
	 */
	private $name;
	/**
	 * @return string
	 */
	public function getName() {
	    return $this->name;
	}
	/**
	 * @param string $newname
	 */
	public function setName($name) {
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
	public function getUsers() {
	    return $this->users;
	}
	
	/**
	 * @ManyToMany(targetEntity="RZ\Renzo\Core\Entities\Role")
	 * @JoinTable(name="groups_roles",
     *      joinColumns={@JoinColumn(name="group_id", referencedColumnName="id")},
     *      inverseJoinColumns={@JoinColumn(name="role_id", referencedColumnName="id")}
     * )
	 * @var ArrayCollection
	 */
	private $roles;
	private $rolesNames = null;
	/**
     * Get roles entities
	 * @return ArrayCollection
	 */
	public function getRolesEntities() {
	    return $this->roles;
	}
    /**
     * Get roles names as a simple array
     * @return array
     */
    public function getRoles() {
        if ($this->rolesNames === null) {
            $this->rolesNames = array();
            foreach ($this->getRolesEntities() as $role) {
                $this->rolesNames[] = $role->getName();
            }
        }
        return $this->rolesNames;
    }
    public function addRole(Role $role)
    {
        if (!$this->getRolesEntities()->contains($role)) {
            $this->getRolesEntities()->add($role);
        }
        return $this;
    }

    public function removeRole(Role $role)
    {
        if ($this->getRolesEntities()->contains($role)) {
            $this->getRolesEntities()->removeElement($role);
        }
        return $this;
    }
	
	public function __construct()
    {
    	parent::__construct();

    	$this->roles = new ArrayCollection();
    	$this->users = new ArrayCollection();
    	$this->rolesNames = null;
    }
}