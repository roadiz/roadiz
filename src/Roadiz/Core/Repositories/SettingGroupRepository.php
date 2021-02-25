<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Repositories;

/**
 * @package RZ\Roadiz\Core\Repositories
 * @extends EntityRepository<\RZ\Roadiz\Core\Entities\SettingGroup>
 */
class SettingGroupRepository extends EntityRepository
{

    /**
     * @param string $name
     *
     * @return bool
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function exists($name)
    {
        $query = $this->_em->createQuery('
            SELECT COUNT(s.id) FROM RZ\Roadiz\Core\Entities\SettingGroup s
            WHERE s.name = :name')
                        ->setParameter('name', $name);

        return (boolean) $query->getSingleScalarResult();
    }

    /**
     * @return array
     */
    public function findAllNames()
    {
        $query = $this->_em->createQuery('SELECT s.name FROM RZ\Roadiz\Core\Entities\SettingGroup s');
        return array_map('current', $query->getScalarResult());
    }
}
