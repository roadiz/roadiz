<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file GroupHandler.php
 * @copyright REZO ZERO 2014
 * @author Thomas Aufresne
 */

namespace RZ\Renzo\Core\Handlers;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Role;
use RZ\Renzo\Core\Entities\Group;
use RZ\Renzo\Core\Entities\Translation;

/**
 * Handle operations with Group entities.
 */
class GroupHandler
{
    private $group = null;

    /**
     * Create a new group handler with group to handle.
     *
     * @param RZ\Renzo\Core\Entities\Group $group
     */
    public function __construct(Group $group)
    {
        $this->group = $group;
    }

    /**
     * @return RZ\Renzo\Core\Entities\Group
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @param RZ\Renzo\Core\Entities\Group $group
     *
     * @return $this
     */
    public function setGroup($group)
    {
        $this->group = $group;

        return $this;
    }

    /**
     * This method does not flush ORM. You'll need to manually call it.
     *
     * @param RZ\Renzo\Core\Entities\Group $newGroup
     *
     * @throws \RuntimeException If newGroup param is null
     */
    public function diff(Group $newGroup)
    {
        if (null !== $newGroup) {
            if ("" != $newGroup->getName()) {
                $this->getGroup()->setName($newGroup->getName());
            }

            $existingRolesNames = $this->getGroup()->getRoles();

            foreach ($newGroup->getRolesEntities() as $newRole) {
                if (false == in_array($newRole->getName(), $existingRolesNames)) {
                    $role = Kernel::getInstance()->em()->getRepository('RZ\Renzo\Core\Entities\Role')->findOneByName($newRole->getName());
                    $this->getGroup()->addRole($role);
                }
            }

        } else {
            throw new \RuntimeException("New group is null", 1);
        }
    }
}
