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
use Doctrine\ORM\Mapping as ORM;
use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;
use RZ\Roadiz\Utils\StringHandler;
use Symfony\Component\Security\Core\Role\RoleInterface;

/**
 * Roles are persisted version of string Symfony's roles.
 *
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\RoleRepository")
 * @ORM\Table(name="roles")
 */
class Role extends AbstractEntity implements RoleInterface
{
    const ROLE_DEFAULT = 'ROLE_USER';
    const ROLE_SUPERADMIN = 'ROLE_SUPERADMIN';
    const ROLE_BACKEND_USER = 'ROLE_BACKEND_USER';

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
     * @ORM\ManyToMany(targetEntity="RZ\Roadiz\Core\Entities\Group", mappedBy="roles")
     *
     * @var ArrayCollection
     */
    private $groups;

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * @param \RZ\Roadiz\Core\Entities\Group $group
     *
     * @return \RZ\Roadiz\Core\Entities\Group
     */
    public function addGroup(Group $group)
    {
        if (!$this->getGroups()->contains($group)) {
            $this->getGroups()->add($group);
        }

        return $this;
    }

    /**
     * @param \RZ\Roadiz\Core\Entities\Group $group
     *
     * @return \RZ\Roadiz\Core\Entities\Group
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
