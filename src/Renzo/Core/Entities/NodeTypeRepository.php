<?php 


namespace RZ\Renzo\Core\Entities;

use Doctrine\ORM\EntityRepository;

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
        $query = Kernel::getInstance()->em()
                        ->createQuery('
            SELECT partial nt.{id,name} FROM RZ\Renzo\Core\Entities\NodeType nt'
                        );
        try {
            return $query->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }
}