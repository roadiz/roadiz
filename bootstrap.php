<?php 
// Include Composer Autoload (relative to project root).
require_once "vendor/autoload.php";

use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

$paths = array("src/Renzo/Entities", "sources/GeneratedNodeSources");
$isDevMode = true;

// the connection configuration
$dbParams = array(
    'driver'   => 'pdo_mysql',
    'user'     => 'essaiDoctrine',
    'password' => 'essaiDoctrine',
    'dbname'   => 'essaiDoctrine',
);

$config = Setup::createAnnotationMetadataConfiguration($paths, $isDevMode);
$entityManager = EntityManager::create($dbParams, $config);