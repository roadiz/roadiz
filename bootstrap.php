<?php 

define('RENZO_ROOT', dirname(__FILE__));
// Include Composer Autoload (relative to project root).
require_once "vendor/autoload.php";

use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use RZ\Renzo\Core\Kernel;

$paths = array(
	"src/Renzo/Core/Entities", 
	"src/Renzo/Core/AbstractEntities", 
	"sources/GeneratedNodeSources"
);
$isDevMode = true;

if (file_exists(RENZO_ROOT.'/conf/config.json')) {

	Kernel::getInstance()->setConfig(
		json_decode(file_get_contents(RENZO_ROOT.'/conf/config.json'), true)
	);
	// the connection configuration
	$dbParams = Kernel::getInstance()->getConfig()["doctrine"];

	$configDB = Setup::createAnnotationMetadataConfiguration($paths, $isDevMode);
	Kernel::getInstance()->setEntityManager(EntityManager::create($dbParams, $configDB));
}
else {
	echo "No configuration found (conf/config.json)".PHP_EOL;
	exit();
}