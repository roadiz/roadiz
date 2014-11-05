<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file NodesSourcesDocumentsRepository.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Repositories;

use RZ\Renzo\Core\Entities\NodeTypeField;
use RZ\Renzo\Core\Entities\NodesToNodes;
use RZ\Renzo\Core\Entities\Node;
use RZ\Renzo\Core\Kernel;

/**
 * {@inheritdoc}
 */
class NodesToNodesRepository extends EntityRepository
{
    /**
     * @param RZ\Renzo\Core\Entities\NodesToNodes  $node
     * @param RZ\Renzo\Core\Entities\NodeTypeField $field
     *
     * @return integer
     */
    public function getLatestPosition(Node $node, NodeTypeField $field)
    {
        $query = $this->_em->createQuery('
            SELECT MAX(ntn.position) FROM RZ\Renzo\Core\Entities\NodesToNodes ntn
            WHERE ntn.nodeA = :nodeA AND ntn.field = :field')
                    ->setParameter('nodeA', $node)
                    ->setParameter('field', $field);

        try {
            return (int) $query->getSingleScalarResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return 0;
        }
    }
}
