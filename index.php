<?php

use RZ\Renzo\Core\Kernel;

require 'bootstrap.php';

if (php_sapi_name() == 'cli') {
    echo 'Use "bin/renzo" as an executable instead of calling index.php'.PHP_EOL;
} else {
    Kernel::getInstance()->runApp();
}
