<?php 

use RZ\Renzo\Entities\Node;
use RZ\Renzo\Entities\NodeType;
use RZ\Renzo\Entities\NodeTypeField;


require_once 'bootstrap.php';

RZ\Renzo\Core\Kernel::getInstance();

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



$types = $entityManager->getRepository('RZ\Renzo\Entities\NodeType')->findAll();

foreach ($types as $type) {
	$type->generateSourceEntityClass();
}