<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Repositories;

use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodeTypeField;

/**
 * @package RZ\Roadiz\Core\Repositories
 * @extends EntityRepository<\RZ\Roadiz\Core\Entities\NodesCustomForms>
 */
class NodesCustomFormsRepository extends EntityRepository
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
            SELECT MAX(ncf.position) FROM RZ\Roadiz\Core\Entities\NodesCustomForms ncf
            WHERE ncf.node = :node AND ncf.field = :field')
                    ->setParameter('node', $node)
                    ->setParameter('field', $field);

        return (int) $query->getSingleScalarResult();
    }
}
