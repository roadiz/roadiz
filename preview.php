<?php
/**
 * @deprecated Use query string _preview param
 */
declare(strict_types=1);

use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\HttpFoundation\Request;
/*
 * This is preview entry point.
 *
 * This allows Backend users to preview nodes pages
 * that has not been published yet.
 */
/** @deprecated Use Kernel::getProjectDir()  */
define('ROADIZ_ROOT', dirname(__FILE__));
require dirname(realpath(__FILE__)) . "/bootstrap.php";

$kernel = new Kernel('prod', false, true);
$request = Request::createFromGlobals();

$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
