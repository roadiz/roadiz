<?php 


namespace RZ\Renzo\Core\Entities;

use RZ\Renzo\Core\Utils\EntityRepository;

use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Core\Kernel;

/**
* 
*/
class NodeTypeRepository extends EntityRepository
{	

    /**
     * Get all node-types names from PARTIAL objects
     * 
     * @return array
     */
	public function findAllNames()
    {
        $query = $this->_em->createQuery('
            SELECT partial nt.{id,name} FROM RZ\Renzo\Core\Entities\NodeType nt'
                        );
        try {
            return $query->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }
}