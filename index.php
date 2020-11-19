<?php
declare(strict_types=1);

use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\HttpFoundation\Request;
use Symfony\Component\Dotenv\Dotenv;

if (PHP_VERSION_ID < 70200) {
    echo 'Your PHP version is ' . phpversion() . "." . PHP_EOL;
    echo 'You need a least PHP version 7.2.0.' . PHP_EOL;
    exit(1);
}

/** @deprecated Use Kernel::getProjectDir()  */
define('ROADIZ_ROOT', dirname(__FILE__));
// Include Composer Autoload (relative to project root).
require("vendor/autoload.php");

$kernel = new Kernel('prod', false);
$dotenv = new Dotenv();
$dotenv->load($kernel->getProjectDir() . '/.env');
$request = Request::createFromGlobals();

$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
