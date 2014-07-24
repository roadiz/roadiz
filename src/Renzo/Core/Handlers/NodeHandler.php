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
		foreach ($this->getNode()->getChildren() as $node) {
			$node->getHandler()->removeWithChildrenAndAssociations();
		}

		return $this;
	}

	public function removeAssociations()
	{
		foreach ($this->getNode()->getNodeSources() as $ns) {
			Kernel::getInstance()->em()->remove($ns);
		}
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

	/**
	 * Return every node’s parents
	 * @return array
	 */
	public function getParents()
	{
		$parentsArray = array();
		$parent = $this->getNode();

		do {
			$parent = $parent->getParent();
			if ($parent !== null) {
				$parentsArray[] = $parent;
			}
			else break;
		} while ($parent !== null);

		return array_reverse($parentsArray);
	}

	/**
	 * Clean position for current node according to its
	 * @return int Return the next position after the **last** node
	 */
	public function cleanPositions()
	{
		if ($this->getNode()->getParent() !== null) {
			return $this->getNode()->getParent()->getHandler()->cleanChildrenPositions();
		}
		else {
			return static::cleanRootNodesPositions();
		}
	}

	/**
	 * Reset current node children positions
	 * @return int Return the next position after the **last** node
	 */
	public function cleanChildrenPositions()
	{
		$children = $this->getNode()->getChildren();
		$i = 1;
		foreach ($children as $child) {
			$child->setPosition($i);
			$i++;
		}

		Kernel::getInstance()->em()->flush();

		return $i;
	}

	/**
	 * Reset every root nodes positions
	 * @return int Return the next position after the **last** node
	 */
	public static function cleanRootNodesPositions()
	{
		$nodes = Kernel::getInstance()->em()
			->getRepository('RZ\Renzo\Core\Entities\Node')
			->findBy(array('parent' => null), array('position'=>'ASC'));

		$i = 1;
		foreach ($nodes as $child) {
			$child->setPosition($i);
			$i++;
		}

		Kernel::getInstance()->em()->flush();

		return $i;
	}	
}