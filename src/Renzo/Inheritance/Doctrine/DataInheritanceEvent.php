<?php 

namespace RZ\Renzo\Inheritance\Doctrine;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\NodeType;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
 
class DataInheritanceEvent {
 
	public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
	{
 
		// the $metadata is all the mapping info for this class
		$metadata = $eventArgs->getClassMetadata();
 
		// the annotation reader accepts a ReflectionClass, which can be
		// obtained from the $metadata
		$class = $metadata->getReflectionClass();
 
		if ($class->getName() === 'RZ\Renzo\Core\Entities\NodesSources') {

			try {
				if ($this->checkTable('NodeType')) {

					/**
					 *  List node types
					 */
					$nodeTypes = Kernel::getInstance()->em()
						->getRepository('RZ\Renzo\Core\Entities\NodeType')
						->findAll();

					$map = array();
					foreach ($nodeTypes as $type) {
						$map[$type->getName()] = NodeType::getGeneratedEntitiesNamespace().'\\'.$type->getSourceEntityClassName();
					}

					$metadata->setDiscriminatorMap($map);
				}

			}
			catch(Doctrine\DBAL\DBALException $e){
				echo $e->getMessage();
			}
		}
	}

	public function checkTable($table)
	{
	    $conn = Kernel::getInstance()->em()->getConnection();
	    $sm = $conn->getSchemaManager();
	    $tables = $sm->listTables();

	    foreach ($tables as $table) {
	        if ($table->getName() == $table) {
	        	return true;
	        }
	    }

	    return false;
	}
}