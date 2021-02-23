<?php
declare(strict_types=1);

namespace RZ\Roadiz\Attribute\Repository;

use RZ\Roadiz\Attribute\Model\AttributeInterface;
use RZ\Roadiz\Core\Repositories\EntityRepository;

/**
 * @package RZ\Roadiz\Attribute\Repository
 */
final class AttributeDocumentsRepository extends EntityRepository
{
    /**
     * @param AttributeInterface $attribute
     *
     * @return int
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getLatestPosition(AttributeInterface $attribute)
    {
        $qb = $this->createQueryBuilder('ad');
        $qb->select($qb->expr()->max('ad.position'))
            ->andWhere($qb->expr()->eq('ad.attribute', ':attribute'))
            ->setParameter('attribute', $attribute);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
