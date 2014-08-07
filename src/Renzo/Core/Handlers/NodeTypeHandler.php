<?php 

namespace RZ\Renzo\Core\Handlers;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Node;
use RZ\Renzo\Core\Entities\NodeType;
use RZ\Renzo\Core\Entities\NodeTypeField;
use RZ\Renzo\Core\Entities\Translation;

use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;

use Doctrine\DBAL\Schema\Column;

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
    			$fieldsArray[] = $field->getHandler()->generateSourceField();
    			if ($field->isIndexed()) {
    				$indexes[] = $field->getHandler()->generateSourceFieldIndex();
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
     * Update database schema for current node-type.
     * @return NodeTypeHandler
     */
    public function updateSchema()
    {
        \RZ\Renzo\Console\SchemaCommand::refreshMetadata();
        $this->removeSourceEntityClass();
        $this->generateSourceEntityClass();

    	return $this;
    }

    /**
     * Delete node-type class from database.
     * @return NodeTypeHandler
     */
    public function deleteSchema()
    {
        $this->removeSourceEntityClass();
        \RZ\Renzo\Console\SchemaCommand::refreshMetadata();
        return $this;
    }
    /**
     * Delete node-type inherited nodes and its database schema
     * before removing it from node-types table.
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

    /**
     * Serializes data into Json.
     * @return string         
     */
    public function serializeToJson() {
        $data = array();
        // Reports information about the class NodeType
        $nodeTypeInfos = new \ReflectionClass($this->getNodeType());
        $data['node_type'] = array();

        $data['node_type']['name'] = $this->getNodeType()->getName();
        $data['node_type']['displayName'] = $this->getNodeType()->getDisplayName();
        $data['node_type']['description'] = $this->getNodeType()->getDescription();
        $data['node_type']['visible'] = $this->getNodeType()->isVisible();
        $data['node_type']['newsletterType'] = $this->getNodeType()->isNewsletterType();
        $data['node_type']['hidingNodes'] = $this->getNodeType()->isHidingNodes();
        $data['node_type']['fields'] = array();

        foreach ($this->getNodeType()->getFields() as $ntf) {
            $data['node_type']['fields'][] = $ntf->getHandler()->serialize();
        }

        if (defined(JSON_PRETTY_PRINT)) {
            return json_encode($data, JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        else {
            return json_encode($data, JSON_NUMERIC_CHECK);
        }
    }

    /**
     * Export Json into a .rzt file
     * @return void
     */
    public function exportIntoFile() {
        if ($this->getObject()->exists()) {
            $serialized = $this->serializeToJson();
            $file = $this->getObject()->name.".rzt";

            if (file_put_contents($file, $serialized, LOCK_EX)) {
                $size = filesize($file); 

                header('Content-Type: application/force-download; name=' . $this->getObject()->name . '.rzt');
                header("Content-Transfer-Encoding: binary");
                header("Content-Length: ".$size);
                header('Content-Disposition: attachment; filename='.$this->getObject()->name.'.rzt');
                header("Expires: 0"); 
                header("Cache-Control: no-cache, must-revalidate"); 
                readfile($file);
                exit();
            }
        }
    }

    /**
     * Import Node Type datas from a .rzt file.
     * @param  string  $url
     * @return void
     */
    public function importFromFile($url) {
        if (file_exists($url)) {
            if ($serialized = file_get_contents($url)) {
                if ($nodeType = json_decode($serialized, true)) {

                    $exists = new NodeType(array('name'=>$nodeType["name"]));

                    if (!$exists->exists()) {
                        foreach (NodeType::listTableColumns() as $column) {
                            if (isset($nodeType[$column])) {
                                $exists->$column = $nodeType[$column];
                            }
                        }

                        if ($exists->insertIntoDB() !== false) {   // ?
                            foreach ($nodeType["node_type_fields"] as $field)  {
                                $newField = new NodeTypeField();
                                $newField->getId() = $exists->getId(); // ?

                                foreach ($field as $key => $value) {
                                    $newField->$key = $value;
                                }

                                if ($newField->insertIntoDB() !== false) { // ?
                                    Kernel::getInstance()->em()->persist($newField);
                                    Kernel::getInstance()->em()->flush();
                                }
                            }
                            /*
                            rz_log::register_message(sprintf(_("Node type “%s” has been succesfully imported"), $exists->name), CONFIRM);
                            rz_log::postUserLog(sprintf(_("Node type “%s” has been succesfully imported."), $exists->name));
                            */
                            Kernel::getInstance()->em()->flush();
                            return true;
                        }
                        else {
                            return false;
                        }
                    }
                     else {
                        throw new Exception("Node type file cannot be unserialized.!", 1);
                        return false;
                    }
                }
                else {
                    throw new Exception("Node type file cannot be unserialized.", 1);
                    return false;
                }
            }
            else {
                throw new Exception("Node type file cannot be read.", 1);
                return false;
            }
        }
        else {
            throw new Exception("Node type file cannot be found.", 1);
            return false;
        }
    }

    /**
     * Update an existing Node Type.
     * @param string  $url
     * @return bool
     */
    public function updateFromFile($url) {
        if (file_exists($url)) {
            if ($serialized = file_get_contents($url)) {
                if ($nodeType = json_decode($serialized, true)) {
                    
                    $exists = new NodeType(array('name'=>$nodeType["name"]));
                   
                    if ($exists->exists()) {
                        $NewNodeTypes = $nodeType["node_type_fields"];
                        $OldNodeTypesNames = $exists->getFields();

                        try {

                            Kernel::getInstance()->em()
                                ->find('RZ\Renzo\Core\Entities\NodeType', (int)$exists->getId());

                            foreach ($NewNodeTypes as $key => $field) {
                                if (!in_array($field["name"], $OldNodeTypesNames)) {

                                    $newField = new NodeTypeField();
                                    $newField->getId() = $exists->getId(); // ?

                                    foreach ($field as $key => $value) {
                                        $newField->$key = $value;
                                    }

                                    if ($newField->insertIntoDB() !== false) { // ?
                                        Kernel::getInstance()->em()->persist($newField);
                                        Kernel::getInstance()->em()->flush();
                                        rz_log::register_message(sprintf(_("New field “%s” for “%s” node type!"), 
                                            $newField->name, $exists->name), CONFIRM); // ?
                                    }
                                }
                            }

                            return true;
                        }
                        catch (PDOException $e) {
                            Kernel::getInstance()->em()->flush();
                            return false;
                        }
                    }
                    else {
                        throw new Exception("Node type “%s” does not exist!", 1);
                        return false;
                    }
                }
                else {
                    throw new Exception("Node type file cannot be unserialized.", 1);
                    return false;
                }
            }
            else {
                throw new Exception("Node type file cannot be read.", 1);
                return false;
            }
        }
        else {
            throw new Exception("Node type file cannot be found.", 1);
            return false;
        }
    }
}