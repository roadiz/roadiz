<?php
declare(strict_types=1);

namespace RZ\Roadiz\Attribute\Repository;

use RZ\Roadiz\Core\Entities\Attribute;
use RZ\Roadiz\Core\Repositories\EntityRepository;

/**
 * {@inheritdoc}
 */
final class AttributeDocumentsRepository extends EntityRepository
{
    /**
     * @param Attribute $attribute
     *
     * @return integer
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getLatestPosition(Attribute $attribute)
    {
        $qb = $this->createQueryBuilder('ad');
        $qb->select($qb->expr()->max('ad.position'))
            ->andWhere($qb->expr()->eq('ad.attribute', ':attribute'))
            ->setParameter('attribute', $attribute);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
