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
class NodeTypeHandler {
	private $nodeType = null;

	/**
	 * @return RZ\Renzo\Core\Entities\NodeType
	 */
	public function getNodeType() {
	    return $this->nodeType;
	}
	
	/**
	 * @param RZ\Renzo\Core\Entities\NodeType $newnode
	 */
	public function setNodeType($nodeType) {
	    $this->nodeType = $nodeType;
	
	    return $this;
	}

	public function __construct( NodeType $nodeType )
	{
		$this->nodeType = $nodeType;
	}

    /**
     * Remove node type entity class file from server
     * @return boolean
     */
    public function removeSourceEntityClass()
    {
    	$folder = RENZO_ROOT.'/sources/'.NodeType::getGeneratedEntitiesNamespace();
    	$file = $folder.'/'.$this->getNodeType()->getSourceEntityClassName().'.php';

    	if (file_exists($file)) {
    		return unlink($file);
    	}

    	return false;
    }

    /**
     * Generate Doctrine entity class for current nodetype
     * 
     * @return string
     */
    public function generateSourceEntityClass()
    {
    	$folder = RENZO_ROOT.'/sources/'.NodeType::getGeneratedEntitiesNamespace();
    	$file = $folder.'/'.$this->getNodeType()->getSourceEntityClassName().'.php';

    	if (!file_exists($folder)) {
    		mkdir($folder, 0755, true);
    	}

    	if (!file_exists($file)) {

    		$fields = $this->getNodeType()->getFields();
    		$fieldsArray = array();
    		$indexes = array();
    		foreach ($fields as $field) {
    			$fieldsArray[] = $field->generateSourceField();
    			if ($field->isIndexed()) {
    				$indexes[] = $field->generateSourceFieldIndex();
    			}
    		}

	    	$content = '<?php
/**
 * THIS IS A GENERATED FILE, DO NOT EDIT IT
 * IT WILL BE RECREATED AT EACH NODE-TYPE UPDATE
 */
namespace '.NodeType::getGeneratedEntitiesNamespace().';

use RZ\Renzo\Core\AbstractEntities\PersistableObject;
use RZ\Renzo\Core\Entities\NodesSources;

/**
 * @Entity
 * @Table(name="'.$this->getNodeType()->getSourceEntityTableName().'", indexes={'.implode(',', $indexes).'})
 */
class '.$this->getNodeType()->getSourceEntityClassName().' extends NodesSources
{

	'.implode('', $fieldsArray).'
}';
			file_put_contents($file, $content);
			return "Source class “".$this->getNodeType()->getSourceEntityClassName()."” has been created.".PHP_EOL;
    	}
    	else {
    		return "Source class “".$this->getNodeType()->getSourceEntityClassName()."” already exists.".PHP_EOL;
    	}

		return false;
    }

    /**
     * Update database schema for current node-type
     * @return NodeTypeHandler
     */
    public function updateSchema()
    {
    	$this->removeSourceEntityClass();
    	$this->generateSourceEntityClass();
    	\RZ\Renzo\Console\SchemaCommand::updateSchema();

    	return $this;
    }

    /**
     * Delete node-type class from database
     * @return NodeTypeHandler
     */
    public function deleteSchema()
    {
        $this->removeSourceEntityClass();
        \RZ\Renzo\Console\SchemaCommand::updateSchema();

        return $this;
    }
    /**
     * Delete node-type inherited nodes and its database schema
     * before removing it from node-types table
     * 
     * @return NodeTypeHandler
     */
    public function deleteWithAssociations()
    {
        /*
         * Delete every nodes
         */
        $nodes = Kernel::getInstance()->em()
            ->getRepository('RZ\Renzo\Core\Entities\Node')
            ->findBy(array(
                'nodeType' => $this->getNodeType()
            ));

        foreach ($nodes as $node) {
            $node->getHandler()->removeWithChildrenAndAssociations();
        }

        /*
         * Remove class and database table
         */
        $this->deleteSchema();

        /*
         * Remove node type
         */
        Kernel::getInstance()->em()->remove($this->getNodeType());
        Kernel::getInstance()->em()->flush();

        return $this;
    }
}