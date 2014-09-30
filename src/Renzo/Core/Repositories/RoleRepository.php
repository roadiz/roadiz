<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file TagRepository.php
 * @copyright REZO ZERO 2014
 * @author Thomas Aufresne
 */
namespace RZ\Renzo\Core\Repositories;

use RZ\Renzo\Core\Entities\Role;
use RZ\Renzo\Core\Kernel;

/**
 * {@inheritdoc}
 */
class RoleRepository extends EntityRepository
{
    /**
     * @param string $roleName
     *
     * @return RZ\Renzo\Core\Entities\Role or null
     */
    public function countByName($roleName)
    {
        $roleName = Role::cleanName($roleName);

        $query = $this->_em->createQuery('
            SELECT COUNT(r) FROM RZ\Renzo\Core\Entities\Role r
            WHERE r.name = :name')
        ->setParameter('name', $roleName);

        return $query->getSingleScalarResult();
    }

    /**
     * @param string $roleName
     *
     * @return RZ\Renzo\Core\Entities\Role
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
                SELECT r FROM RZ\Renzo\Core\Entities\Role r
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
            SELECT r.name FROM RZ\Renzo\Core\Entities\Role r
            WHERE r.name != :name')
            ->setParameter('name', Role::ROLE_SUPERADMIN);

        $rolesNames = $query->getScalarResult();

        foreach ($rolesNames as $role) {
            $names[] = $role['name'];
        }

        return $names;
    }
}
