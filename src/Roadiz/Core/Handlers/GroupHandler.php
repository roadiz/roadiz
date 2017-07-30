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
 * @file GroupHandler.php
 * @author Thomas Aufresne
 */
namespace RZ\Roadiz\Core\Handlers;

use RZ\Roadiz\Core\Entities\Group;

/**
 * Handle operations with Group entities.
 */
class GroupHandler extends AbstractHandler
{
    private $group;

    /**
     * Create a new group handler with group to handle.
     *
     * @param Group|null $group
     */
    public function __construct(Group $group = null)
    {
        parent::__construct();
        $this->group = $group;
    }

    /**
     * @return Group
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @param Group $group
     * @return $this
     */
    public function setGroup(Group $group)
    {
        $this->group = $group;
        return $this;
    }

    /**
     * This method does not flush ORM. You'll need to manually call it.
     *
     * @param \RZ\Roadiz\Core\Entities\Group $newGroup
     *
     * @throws \RuntimeException If newGroup param is null
     */
    public function diff(Group $newGroup)
    {
        if (null !== $newGroup) {
            if ("" != $newGroup->getName()) {
                $this->group->setName($newGroup->getName());
            }

            $existingRolesNames = $this->group->getRoles();

            foreach ($newGroup->getRolesEntities() as $newRole) {
                if (false === in_array($newRole->getName(), $existingRolesNames)) {
                    $role = $this->entityManager->getRepository('RZ\Roadiz\Core\Entities\Role')
                                                 ->findOneByName($newRole->getName());
                    $this->group->addRole($role);
                }
            }
        } else {
            throw new \RuntimeException("New group is null", 1);
        }
    }
}
