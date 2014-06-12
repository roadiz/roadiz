<?php 

use RZ\Renzo\Core\Kernel;

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