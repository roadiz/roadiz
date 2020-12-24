<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;
use JMS\Serializer\Annotation as Serializer;

/**
 * A group gather User and Roles.
 *
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\EntityRepository")
 * @ORM\Table(name="usergroups")
 */
class Group extends AbstractEntity
{
    /**
     * @ORM\Column(type="string", unique=true)
     * @Serializer\Groups({"user", "role", "group"})
     * @Serializer\Type("string")
     * @var string
     */
    private $name = '';
    /**
     * @ORM\ManyToMany(targetEntity="RZ\Roadiz\Core\Entities\User", mappedBy="groups")
     * @Serializer\Groups({"group_user"})
     * @Serializer\Type("ArrayCollection<RZ\Roadiz\Core\Entities\User>")
     * @var Collection<User>
     */
    private $users;
    /**
     * @ORM\ManyToMany(targetEntity="RZ\Roadiz\Core\Entities\Role", inversedBy="groups", cascade={"persist", "merge"})
     * @ORM\JoinTable(name="groups_roles",
     *      joinColumns={@ORM\JoinColumn(name="group_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="role_id", referencedColumnName="id")}
     * )
     * @var Collection<Role>
     * @Serializer\Groups({"group"})
     * @Serializer\Type("ArrayCollection<RZ\Roadiz\Core\Entities\Role>")
     * @Serializer\Accessor(getter="getRolesEntities", setter="setRolesEntities")
     */
    private $roles;
    /**
     * @var array|null
     * @Serializer\Groups({"group", "user"})
     * @Serializer\Type("array<string>")
     */
    private $rolesNames = null;

    /**
     * Create a new Group.
     */
    public function __construct()
    {
        $this->roles = new ArrayCollection();
        $this->users = new ArrayCollection();
        $this->rolesNames = null;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    /**
     * Get roles names as a simple array.
     *
     * @return string[]
     */
    public function getRoles(): array
    {
        if ($this->rolesNames === null) {
            $this->rolesNames = array_map(function (Role $role) {
                return $role->getRole();
            }, $this->getRolesEntities()->toArray());
        }

        return $this->rolesNames;
    }

    /**
     * Get roles entities.
     *
     * @return Collection
     */
    public function getRolesEntities(): ?Collection
    {
        return $this->roles;
    }

    /**
     * Get roles entities.
     *
     * @param Collection $roles
     *
     * @return Group
     */
    public function setRolesEntities(Collection $roles): self
    {
        $this->roles = $roles;
        /** @var Role $role */
        foreach ($this->roles as $role) {
            $role->addGroup($this);
        }
        return $this;
    }

    /**
     * @param Role $role
     *
     * @return $this
     */
    public function addRole(Role $role): Group
    {
        if (!$this->roles->contains($role)) {
            $this->roles->add($role);
        }

        return $this;
    }

    /**
     * @param Role $role
     *
     * @return $this
     */
    public function removeRole(Role $role): Group
    {
        if ($this->roles->contains($role)) {
            $this->roles->removeElement($role);
        }

        return $this;
    }
}
