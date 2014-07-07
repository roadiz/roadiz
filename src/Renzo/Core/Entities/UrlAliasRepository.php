<?php 


namespace RZ\Renzo\Core\Entities;

use Doctrine\ORM\EntityRepository;

use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Core\Kernel;

/**
* 
*/
class UrlAliasRepository extends EntityRepository
{	

    /**
     * Get all url aliases linked to given node
     * 
     * @return array
     */
	public function findAllFromNode( $node_id )
    {
        $query = Kernel::getInstance()->em()
                        ->createQuery('
            SELECT ua FROM RZ\Renzo\Core\Entities\UrlAlias ua 
            INNER JOIN ua.nodeSource ns 
            INNER JOIN ns.node n 
            WHERE n.id = :nodeId
        ')->setParameter('nodeId', (int)$node_id);

        try {
            return $query->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }
}