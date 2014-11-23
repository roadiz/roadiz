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
 * @file RoleRepository.php
 * @copyright REZO ZERO 2014
 * @author Thomas Aufresne
 */
namespace RZ\Roadiz\Core\Repositories;

use RZ\Roadiz\Core\Entities\Role;
use RZ\Roadiz\Core\Kernel;

/**
 * {@inheritdoc}
 */
class RoleRepository extends EntityRepository
{
    /**
     * @param string $roleName
     *
     * @return RZ\Roadiz\Core\Entities\Role or null
     */
    public function countByName($roleName)
    {
        $roleName = Role::cleanName($roleName);

        $query = $this->_em->createQuery('
            SELECT COUNT(r) FROM RZ\Roadiz\Core\Entities\Role r
            WHERE r.name = :name')
        ->setParameter('name', $roleName);

        return $query->getSingleScalarResult();
    }

    /**
     * @param string $roleName
     *
     * @return RZ\Roadiz\Core\Entities\Role
     */
    public function findOneByName($roleName)
    {
        $roleName = Role::cleanName($roleName);

        if (0 == $this->countByName($roleName)) {
            $role = new Role($roleName);
            $this->_em->persist($role);
            $this->_em->flush();

            return $role;
        } else {
            $query = $this->_em->createQuery('
                SELECT r FROM RZ\Roadiz\Core\Entities\Role r
                WHERE r.name = :name')
                ->setParameter('name', $roleName);

            return $query->getSingleResult();
        }
    }

    /**
     * Get every Roles names except for ROLE_SUPERADMIN.
     *
     * @return array
     */
    public function getAllBasicRoleName()
    {
        $names = array();

        $query = $this->_em->createQuery('
            SELECT r.name FROM RZ\Roadiz\Core\Entities\Role r
            WHERE r.name != :name')
            ->setParameter('name', Role::ROLE_SUPERADMIN);

        $query->useResultCache(true, 3600, 'RZRoleAll');

        $rolesNames = $query->getScalarResult();

        foreach ($rolesNames as $role) {
            $names[] = $role['name'];
        }

        return $names;
    }
}
