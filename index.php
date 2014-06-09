<?php 

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Node;
use RZ\Renzo\Core\Entities\NodeType;
use RZ\Renzo\Core\Entities\NodeTypeField;

require_once 'bootstrap.php';


if (php_sapi_name() == 'cli' || 
	(isset($_SERVER['argc']) && 
		is_numeric($_SERVER['argc']) && 
	    $_SERVER['argc'] > 0)) {

	Kernel::getInstance()->runConsole();
}
else {
	Kernel::getInstance()->runApp();
}

/*$pageType = new NodeType();
$pageType->setName('projet')
	->setVisible(1);

$entityManager->persist($pageType);

$field = new NodeTypeField();
$field->setName('title')
	->setLabel("Titre de votre page")
	->setType(NodeTypeField::STRING_T)
	->setIndexed(true)
	->setNodeType($pageType);

$field2 = new NodeTypeField();
$field2->setName('sold')
	->setLabel("Projet vendu")
	->setType(NodeTypeField::BOOLEAN_T)
	->setIndexed(true)
	->setNodeType($pageType);

$entityManager->persist($field);
$entityManager->persist($field2);
$entityManager->flush();*/



/*$types = $entityManager->getRepository('RZ\Renzo\Entities\NodeType')->findAll();

foreach ($types as $type) {
	$type->generateSourceEntityClass();
}*/