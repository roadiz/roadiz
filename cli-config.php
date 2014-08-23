<?php
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use RZ\Renzo\Core\Kernel;

// replace with file to your own project bootstrap
require_once 'bootstrap.php';

return ConsoleRunner::createHelperSet(Kernel::getInstance()->em());
