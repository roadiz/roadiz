<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Repositories;

use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;

class LogRepository extends EntityRepository
{
    /**
     * Find latest Log with NodesSources.
     *
     * @param integer $maxResult
     * @return Paginator
     */
    public function findLatestByNodesSources($maxResult = 5)
    {
        /*
         * We need to split this query in 2 for performance matter.
         */
        $qb = $this->createQueryBuilder('log');
        $qb->select('log.id');
        $qb->innerJoin('log.nodeSource', 'ns')
            ->addGroupBy('log.id')
            ->orderBy('log.datetime', 'DESC')
            ->setMaxResults($maxResult)
        ;
        $ids = $qb->getQuery()
            ->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true)
            ->getScalarResult();


        $qb2 = $this->createQueryBuilder('log');
        $qb2->addSelect('ns, n, dbf')
            ->andWhere($qb2->expr()->in('log.id', ':id'))
            ->innerJoin('log.nodeSource', 'ns')
            ->innerJoin('ns.documentsByFields', 'dbf')
            ->innerJoin('ns.node', 'n')
            ->setParameter(':id', array_map(function (array $item) {
                return $item['id'];
            }, $ids));

        return new Paginator($qb2->getQuery(), true);
    }
}
