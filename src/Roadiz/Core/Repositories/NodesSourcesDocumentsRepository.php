<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Repositories;

use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\NodeTypeField;

/**
 * @package RZ\Roadiz\Core\Repositories
 * @extends EntityRepository<\RZ\Roadiz\Core\Entities\NodesSourcesDocuments>
 */
class NodesSourcesDocumentsRepository extends EntityRepository
{
    /**
     * @param NodesSources $nodeSource
     * @param NodeTypeField $field
     * @return integer
     */
    public function getLatestPosition(NodesSources $nodeSource, NodeTypeField $field)
    {
        $query = $this->_em->createQuery('
            SELECT MAX(nsd.position) FROM RZ\Roadiz\Core\Entities\NodesSourcesDocuments nsd
            WHERE nsd.nodeSource = :nodeSource AND nsd.field = :field')
                    ->setParameter('nodeSource', $nodeSource)
                    ->setParameter('field', $field);

        return (int) $query->getSingleScalarResult();
    }
}
