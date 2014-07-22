<?php 
namespace RZ\Renzo\Core\Handlers;

use RZ\Renzo\Core\Entities\NodesSources;
use RZ\Renzo\Core\Entities\Document;
use RZ\Renzo\Core\Entities\NodesSourcesDocuments;
use RZ\Renzo\Core\Entities\NodeTypeField;
use RZ\Renzo\Core\Kernel;
use Symfony\Component\Finder\Finder;


class NodesSourcesHandler
{
	protected $nodeSource;

	/**
	 * 
	 * @param RZ\Renzo\Core\Entities\NodesSources
	 */
	function __construct( $nodeSource )
	{
		$this->nodeSource = $nodeSource;
	}

	
	/**
	 * Remove every node-source documents associations for a given field
	 * @param  NodeTypeField $field
	 * @return void
	 */
	public function cleanDocumentsFromField( NodeTypeField $field )
	{
		$nsDocuments = Kernel::getInstance()->em()
				->getRepository('RZ\Renzo\Core\Entities\NodesSourcesDocuments')
				->findBy(array('nodeSource'=>$this->nodeSource, 'field'=>$field));

		foreach ($nsDocuments as $nsDoc) {
			Kernel::getInstance()->em()->remove($nsDoc);
			Kernel::getInstance()->em()->flush();
		}
	}

	/**
	 * 
	 * @param Document      $document [description]
	 * @param NodeTypeField $field    [description]
	 */
	public function addDocumentForField( Document $document, NodeTypeField $field )
	{
		$nsDoc = new NodesSourcesDocuments( $this->nodeSource, $document, $field );

		$latestPosition = Kernel::getInstance()->em()
				->getRepository('RZ\Renzo\Core\Entities\NodesSourcesDocuments')
				->getLatestPosition($this->nodeSource,$field);

		$nsDoc->setPosition($latestPosition + 1);

		Kernel::getInstance()->em()->persist($nsDoc);
		Kernel::getInstance()->em()->flush();
	}

	public function getDocumentsFromFieldName( $fieldName )
	{
		return Kernel::getInstance()->em()
				->getRepository('RZ\Renzo\Core\Entities\Document')
				->findByNodeSourceAndFieldName($this->nodeSource,$fieldName);
	}

	/**
	 * 
	 * @return string
	 */
	public function getUrl()
	{
		$urlTokens = array();
		$urlTokens[] = $this->getIdentifier();

		$parent = $this->getParent();
		if ($parent !== null && !$parent->getNode()->isHome()) {
			do {
				$handler = $parent->getHandler();
				$urlTokens[] = $handler->getIdentifier();

				$parent = $parent->getHandler()->getParent();
			} while ($parent !== null && !$parent->getNode()->isHome());
		}

		/*
		 * If using node-name, we must use shortLocale
		 */
		if ($urlTokens[0] == $this->nodeSource->getNode()->getNodeName()) {
			$urlTokens[] = $this->nodeSource->getTranslation()->getShortLocale();
		}

		$urlTokens[] = Kernel::getInstance()->getRequest()->getBaseUrl();
		
		$urlTokens = array_reverse($urlTokens);

		return implode('/', $urlTokens);
	}

	/**
	 * Get a string describing uniquely the curent nodeSource
	 * Can be the urlAlias or the nodeName
	 * @return string
	 */
	public function getIdentifier()
	{
		$urlalias = $this->nodeSource->getUrlAliases()->first();
		if ($urlalias != null) {
			return $urlalias->getAlias();
		}
		else {
			return $this->nodeSource->getNode()->getNodeName();
		}
	}

	/**
	 * Get parent node-source to get the current translation
	 * 
	 * @return NodesSources
	 */
	public function getParent()
	{	
		$parent = $this->nodeSource->getNode()->getParent();
		if ($parent !== null) {
			$query = Kernel::getInstance()->em()
	                        ->createQuery('
	            SELECT ns FROM RZ\Renzo\Core\Entities\NodesSources ns 
	            WHERE ns.node = :node 
	            AND ns.translation = :translation'
	                        )->setParameter('node', $parent)
	                        ->setParameter('translation', $this->nodeSource->getTranslation());

	        try {
	            return $query->getSingleResult();
	        } catch (\Doctrine\ORM\NoResultException $e) {
	            return null;
	        }
		}
		else {
			return null;
		}
	}

	/**
	 * Get children nodes sources to lock with current translation
	 * 
	 * @return Array or NodesSources
	 */
	public function getChildren()
	{
		 $query = Kernel::getInstance()->em()
                        ->createQuery('
            SELECT ns FROM RZ\Renzo\Core\Entities\NodesSources ns 
            INNER JOIN ns.node n
            WHERE n.parent = :parent 
            AND ns.translation = :translation 
            ORDER BY n.position ASC'
                        )->setParameter('parent', $this->nodeSource->getNode())
                        ->setParameter('translation', $this->nodeSource->getTranslation());

        try {
            return $query->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
	}
}