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
		/*
		 * UrlAliases
		 */
		$aliases = Kernel::getInstance()->em()
			->getRepository('RZ\Renzo\Core\Entities\UrlAlias')
			->findBy(array(
				'node' => $this->getNode()
			));
		foreach ($aliases as $alias) {
			Kernel::getInstance()->em()->remove($alias);
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
}