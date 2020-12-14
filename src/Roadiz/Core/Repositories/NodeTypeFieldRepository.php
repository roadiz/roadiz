<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Repositories;

use RZ\Roadiz\Core\Entities\NodeType;

/**
 * @package RZ\Roadiz\Core\Repositories
 * @extends EntityRepository<\RZ\Roadiz\Core\Entities\NodeTypeField>
 */
class NodeTypeFieldRepository extends EntityRepository
{
    /**
     * @param NodeType|null $nodeType
     * @return array
     */
    public function findAvailableGroupsForNodeType(NodeType $nodeType = null)
    {
        $query = $this->_em->createQuery('
            SELECT partial ntf.{id,groupName} FROM RZ\Roadiz\Core\Entities\NodeTypeField ntf
            WHERE ntf.visible = true
            AND ntf.nodeType = :nodeType
            GROUP BY ntf.groupName
            ORDER BY ntf.groupName ASC
        ')->setParameter(':nodeType', $nodeType);

        return $query->getScalarResult();
    }

    /**
     * @param NodeType $nodeType
     * @return array
     */
    public function findAllNotUniversal(NodeType $nodeType)
    {
        $qb = $this->createQueryBuilder('ntf');
        $qb->andWhere($qb->expr()->eq('ntf.nodeType', ':nodeType'))
            ->andWhere($qb->expr()->eq('ntf.universal', ':universal'))
            ->orderBy('ntf.position', 'ASC')
            ->setParameter(':nodeType', $nodeType)
            ->setParameter(':universal', false);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param NodeType $nodeType
     * @return array
     */
    public function findAllUniversal(NodeType $nodeType)
    {
        $qb = $this->createQueryBuilder('ntf');
        $qb->andWhere($qb->expr()->eq('ntf.nodeType', ':nodeType'))
            ->andWhere($qb->expr()->eq('ntf.universal', ':universal'))
            ->orderBy('ntf.position', 'ASC')
            ->setParameter(':nodeType', $nodeType)
            ->setParameter(':universal', true);

        return $qb->getQuery()->getResult();
    }

    /**
     * Get latest position in nodeType.
     *
     * Parent can be null for tag root
     *
     * @param NodeType $nodeType
     *
     * @return int
     */
    public function findLatestPositionInNodeType(NodeType $nodeType)
    {
        $query = $this->_em->createQuery('
            SELECT MAX(ntf.position)
            FROM RZ\Roadiz\Core\Entities\NodeTypeField ntf
            WHERE ntf.nodeType = :nodeType')
            ->setParameter('nodeType', $nodeType);

        return (int) $query->getSingleScalarResult();
    }
}
