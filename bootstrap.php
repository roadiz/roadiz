<?php 

define('RENZO_ROOT', dirname(__FILE__));
// Include Composer Autoload (relative to project root).
require_once "vendor/autoload.php";

use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

$paths = array("src/Renzo/Entities", "sources/GeneratedNodeSources");
$isDevMode = true;

if (file_exists(RENZO_ROOT.'/conf/config.json')) {
	$config = json_decode(file_get_contents(RENZO_ROOT.'/conf/config.json'), true);
	// the connection configuration
	$dbParams = $config["doctrine"];

	$config = Setup::createAnnotationMetadataConfiguration($paths, $isDevMode);
	$entityManager = EntityManager::create($dbParams, $config);
}
else {
	echo "No configuration found (conf/config.json)".PHP_EOL;
	exit();
}