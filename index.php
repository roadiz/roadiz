<?php

use RZ\Renzo\Core\Kernel;

require_once 'bootstrap.php';

if (php_sapi_name() == 'cli') {
    Kernel::getInstance()->runConsole();
} else {
    Kernel::getInstance()->runApp();
}
