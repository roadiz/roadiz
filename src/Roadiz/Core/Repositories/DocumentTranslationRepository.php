<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Repositories;

use RZ\Roadiz\Core\Entities\DocumentTranslation;

/**
 * Class DocumentTranslationRepository
 * @package RZ\Roadiz\Core\Repositories
 */
class DocumentTranslationRepository extends EntityRepository
{
    /**
     * @param int $id
     * @return DocumentTranslation|null
     */
    public function findOneWithDocument($id)
    {
        $qb = $this->createQueryBuilder('dt');
        $qb->select('dt, d')
            ->innerJoin('dt.document', 'd')
            ->andWhere($qb->expr()->eq('dt.id', ':id'))
            ->setMaxResults(1)
            ->setParameter(':id', $id);

        return $qb->getQuery()->getOneOrNullResult();
    }
}
