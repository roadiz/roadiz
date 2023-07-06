<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Repositories;

use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodeTypeField;

/**
 * @package RZ\Roadiz\Core\Repositories
 * @extends EntityRepository<\RZ\Roadiz\Core\Entities\NodesToNodes>
 */
class NodesToNodesRepository extends EntityRepository
{
    /**
     * @param Node          $node
     * @param NodeTypeField $field
     *
     * @return integer
     */
    public function getLatestPosition(Node $node, NodeTypeField $field)
    {
        $query = $this->_em->createQuery('
            SELECT MAX(ntn.position) FROM RZ\Roadiz\Core\Entities\NodesToNodes ntn
            WHERE ntn.nodeA = :nodeA AND ntn.field = :field')
                    ->setParameter('nodeA', $node)
                    ->setParameter('field', $field);

        return (int) $query->setQueryCacheLifetime(0)->getSingleScalarResult();
    }
}
