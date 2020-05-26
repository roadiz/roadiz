<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Repositories;

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
        $query = $this->createQueryBuilder('log');
        $query->innerJoin('log.nodeSource', 'ns')
            ->addGroupBy('log')
            ->orderBy('log.datetime', 'DESC')
            ->setMaxResults($maxResult);

        return new Paginator($query->getQuery(), true);
    }
}
