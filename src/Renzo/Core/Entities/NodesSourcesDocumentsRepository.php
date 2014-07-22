<?php 


namespace RZ\Renzo\Core\Entities;

use Doctrine\ORM\EntityRepository;

use RZ\Renzo\Core\Entities\NodeTypeField;
use RZ\Renzo\Core\Entities\NodesSourcesDocuments;
use RZ\Renzo\Core\Kernel;

/**
* 
*/
class NodesSourcesDocumentsRepository extends EntityRepository
{	

    public function getLatestPosition( $nodeSource, NodeTypeField $field )
    {
        $query = Kernel::getInstance()->em()
                        ->createQuery('
            SELECT MAX(nsd.position) FROM RZ\Renzo\Core\Entities\NodesSourcesDocuments nsd 
            WHERE nsd.nodeSource = :nodeSource AND nsd.field = :field
        ')->setParameter('nodeSource', $nodeSource)
          ->setParameter('field', $field);

        try {
            return (int)$query->getSingleScalarResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return 0;
        }
    }
}