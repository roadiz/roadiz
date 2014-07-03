<?php 

namespace RZ\Renzo\Core\Handlers;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Node;
use RZ\Renzo\Core\Entities\NodeType;
use RZ\Renzo\Core\Entities\NodeTypeField;
use RZ\Renzo\Core\Entities\Translation;
/**
* 	
*/
class NodeHandler 
{
	private $node = null;

	/**
	 * @return RZ\Renzo\Core\Entities\Node
	 */
	public function getNode() {
	    return $this->node;
	}
	
	/**
	 * @param RZ\Renzo\Core\Entities\Node $newnode
	 */
	public function setNode($node) {
	    $this->node = $node;
	
	    return $this;
	}

	public function __construct( Node $node )
	{
		$this->node = $node;
	}


	private function removeChildren()
	{
		$nodes = Kernel::getInstance()->em()
			->getRepository('RZ\Renzo\Core\Entities\Node')
			->findBy(array(
				'parent' => $this->getNode()
			));

		foreach ($nodes as $node) {
			Kernel::getInstance()->em()->remove($node);
		}

		return $this;
	}

	public function removeAssociations()
	{
		
	}

	public function removeWithChildrenAndAssociations()
	{
		$this->removeChildren();
		$this->removeAssociations();

		Kernel::getInstance()->em()->remove($this->getNode());

		/*
		 * Final flush
		 */
		Kernel::getInstance()->em()->flush();
	}	

	/**
	 * 
	 * @return array Array of Translation
	 */
	public function getAvailableTranslations()
	{
		$query = Kernel::getInstance()->em()
                        ->createQuery('
            SELECT t 
            FROM RZ\Renzo\Core\Entities\Translation t 
            INNER JOIN t.nodeSources ns 
            INNER JOIN ns.node n
            WHERE n.id = :node_id'
                        )->setParameter('node_id', $this->getNode()->getId());

        try {
            return $query->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
	}
	/**
	 * 
	 * @return array Array of Translation id
	 */
	public function getAvailableTranslationsId()
	{
		$query = Kernel::getInstance()->em()
                        ->createQuery('
            SELECT t.id FROM RZ\Renzo\Core\Entities\Node n 
            INNER JOIN n.nodeSources ns 
            INNER JOIN ns.translation t
            WHERE n.id = :node_id'
                        )->setParameter('node_id', $this->getNode()->getId());
		
		try {

			$simpleArray = array();
			$complexArray = $query->getScalarResult();
			foreach ($complexArray as $subArray) {
				$simpleArray[] = $subArray['id'];
			}

            return $simpleArray;
        } catch (\Doctrine\ORM\NoResultException $e) {
           	return array();
        }
	}

	/**
	 * 
	 * @return array Array of Translation
	 */
	public function getUnavailableTranslations()
	{
		$query = Kernel::getInstance()->em()
                        ->createQuery('
            SELECT t FROM RZ\Renzo\Core\Entities\Translation t 
            WHERE t.id NOT IN (:translations_id)'
                        )->setParameter('translations_id', $this->getAvailableTranslationsId());

        try {
            return $query->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
	}

	/**
	 * 
	 * @return array Array of Translation id
	 */
	public function getUnavailableTranslationsId()
	{
		$query = Kernel::getInstance()->em()
                        ->createQuery('
            SELECT t.id FROM RZ\Renzo\Core\Entities\Translation t 
            WHERE t.id NOT IN (:translations_id)'
                        )->setParameter('translations_id', $this->getAvailableTranslationsId());

        try {
            $simpleArray = array();
			$complexArray = $query->getScalarResult();
			foreach ($complexArray as $subArray) {
				$simpleArray[] = $subArray['id'];
			}

            return $simpleArray;
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
	}
}