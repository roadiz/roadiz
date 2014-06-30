<?php 


namespace RZ\Renzo\Core\Entities;

use Doctrine\ORM\EntityRepository;

use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Core\Kernel;

/**
* 
*/
class NodeRepository extends EntityRepository
{	
	/**
	 * 
	 * @param  integer      $node_id     [description]
	 * @param  Translation $translation [description]
	 * @return Node or null
	 */
	public function findWithTranslation($node_id, Translation $translation )
	{
		$qb = Kernel::getInstance()->em()->createQueryBuilder();
	    $qb->select('n, ns')
	        ->from('RZ\Renzo\Core\Entities\Node', 'n')
	        ->where('n.id = :node_id')
	        ->innerJoin('n.nodeSources', 'ns')
	        ->innerJoin('ns.translation', 't', 'WITH', $qb->expr()->eq('t.id', ':translation_id'));

	    $qb->setParameter('node_id', (int)$node_id);
	    $qb->setParameter('translation_id', (int)$translation->getId());

	    return $qb->getQuery()->getSingleResult();
	}
}