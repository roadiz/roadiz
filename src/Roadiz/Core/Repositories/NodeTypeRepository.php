<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Repositories;

/**
 * Class NodeTypeRepository
 *
 * @package RZ\Roadiz\Core\Repositories
 */
class NodeTypeRepository extends EntityRepository
{
    /**
     * @return array
     */
    public function findAll()
    {
        $qb = $this->createQueryBuilder('nt');
        $qb->addSelect('ntf')
            ->leftJoin('nt.fields', 'ntf')
            ->addOrderBy('nt.name', 'ASC')
            ->setCacheable(true);

        return $qb->getQuery()->getResult();
    }
}
