<?php
/**
 * Copyright © 2014, Ambroise Maupate and Julien Blanchet
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
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file Role.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use RZ\Roadiz\Core\AbstractEntities\PersistableInterface;
use RZ\Roadiz\Utils\StringHandler;
use Symfony\Component\Security\Core\Role\Role as BaseRole;
use JMS\Serializer\Annotation as Serializer;

/**
 * Roles are persisted version of string Symfony's roles.
 *
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\RoleRepository")
 * @ORM\Table(name="roles")
 */
class Role extends BaseRole implements PersistableInterface
{
    const ROLE_DEFAULT = 'ROLE_USER';
    const ROLE_SUPERADMIN = 'ROLE_SUPERADMIN';
    const ROLE_BACKEND_USER = 'ROLE_BACKEND_USER';

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return Role
     */
    public function setId($id): Role
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @ORM\Column(type="string", unique=true)
     * @Serializer\Groups({"user", "role", "group"})
     * @Serializer\Type("string")
     * @var string
     */
    private $name;

    /**
     * @param string $role
     * @return Role
     */
    public function setRole(string $role): Role
    {
        $this->name = static::cleanName($role);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getRole(): string
    {
        return $this->name;
    }

    /**
     * @return string
     * @deprecated Use getRole method
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return $this
     * @deprecated Use setRole method
     */
    public function setName(string $name): Role
    {
        return $this->setRole($name);
    }

    /**
     * @param string $name
     *
     * @return string $name
     */
    public static function cleanName(string $name): string
    {
        $name = StringHandler::variablize($name);
        if (0 === preg_match("/^role_/i", $name)) {
            $name = "ROLE_" . $name;
        }

        return strtoupper($name);
    }

    /**
     * @ORM\ManyToMany(targetEntity="RZ\Roadiz\Core\Entities\Group", mappedBy="roles", cascade={"persist", "merge"})
     * @Serializer\Groups({"role"})
     * @Serializer\Type("ArrayCollection<RZ\Roadiz\Core\Entities\Group>")
     * @Serializer\Accessor(getter="getGroups", setter="setGroups")
     * @var Collection<Group>
     */
    private $groups;

    /**
     * @return Collection
     */
    public function getGroups(): Collection
    {
        return $this->groups;
    }

    /**
     * @param Group $group
     * @return $this
     */
    public function addGroup(Group $group): Role
    {
        if (!$this->getGroups()->contains($group)) {
            $this->getGroups()->add($group);
        }

        return $this;
    }

    /**
     * @param Collection $groups
     * @return $this
     */
    public function setGroups(Collection $groups): Role
    {
        $this->groups = $groups;
        /** @var Group $group */
        foreach ($this->groups as $group) {
            $group->addRole($this);
        }

        return $this;
    }

    /**
     * @param \RZ\Roadiz\Core\Entities\Group $group
     * @return $this
     */
    public function removeGroup(Group $group): Role
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
     * @Serializer\Groups({"role"})
     */
    public function getClassName(): string
    {
        return str_replace('_', '-', strtolower($this->getRole()));
    }

    /**
     * @return boolean
     */
    public function required()
    {
        if ($this->getRole() == static::ROLE_DEFAULT ||
            $this->getRole() == static::ROLE_SUPERADMIN ||
            $this->getRole() == static::ROLE_BACKEND_USER) {
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
        $this->name = static::cleanName($name);
        parent::__construct($this->name);

        $this->groups = new ArrayCollection();
    }
}
