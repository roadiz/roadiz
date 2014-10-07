<?php
/*
 * Copyright REZO ZERO 2014
 *
 * Abstract api class
 *
 * @file AbstractApi.php
 * @copyright REZO ZERO 2014
 * @author Maxime Constantinian
 */

namespace RZ\Renzo\CMS\Utils;

use RZ\Renzo\Core\Kernel;

abstract class AbstractApi {

    protected $context;

    function __construct() {
        $this->context = Kernel::getService('securityContext');
    }

    abstract public function getRepository();

    abstract public function getBy( array $criteria );

    abstract public function getOneBy( array $criteria );

}