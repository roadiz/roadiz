<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Repositories;

/**
 * @package RZ\Roadiz\Core\Repositories
 * @extends EntityRepository<\RZ\Roadiz\Core\Entities\Setting>
 */
class SettingRepository extends EntityRepository
{
    /**
     * @param string $name
     *
     * @return int|mixed|string
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getValue($name)
    {
        $builder = $this->createQueryBuilder('s');
        $builder->select('s.value')
                ->andWhere($builder->expr()->eq('s.name', ':name'))
                ->setParameter(':name', $name);

        $query = $builder->getQuery();
        $query->enableResultCache(3600, 'RZSettingValue_'.$name);

        return $query->getSingleScalarResult();
    }

    /**
     * @param string $name
     *
     * @return bool
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function exists($name)
    {
        $builder = $this->createQueryBuilder('s');
        $builder->select($builder->expr()->count('s.value'))
            ->andWhere($builder->expr()->eq('s.name', ':name'))
            ->setParameter(':name', $name);

        $query = $builder->getQuery();
        $query->enableResultCache(3600, 'RZSettingExists_'.$name);

        return (boolean) $query->getSingleScalarResult();
    }

    /**
     * Get every Setting names
     *
     * @return array
     */
    public function findAllNames()
    {
        $builder = $this->createQueryBuilder('s');
        $builder->select('s.name');
        $query = $builder->getQuery();
        $query->enableResultCache(3600, 'RZSettingAll');

        return array_map('current', $query->getScalarResult());
    }
}
