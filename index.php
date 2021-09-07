<?php
declare(strict_types=1);

use RZ\Roadiz\Core\HttpFoundation\Request;

define('ROADIZ_ROOT', dirname(__FILE__));
require dirname(realpath(__FILE__)) . "/bootstrap.php";

$kernel = new \RZ\Roadiz\Core\SourceKernel('prod', false);
$request = Request::createFromGlobals();

$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
