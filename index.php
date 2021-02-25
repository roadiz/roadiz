<?php
declare(strict_types=1);

use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\HttpFoundation\Request;

/** @deprecated Use Kernel::getProjectDir()  */
define('ROADIZ_ROOT', dirname(__FILE__));
require dirname(realpath(__FILE__)) . "/bootstrap.php";

$kernel = new Kernel('prod', false);
$request = Request::createFromGlobals();

$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
