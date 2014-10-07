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

abstract class AbstractApi {

    abstract public function getRepository();

    abstract public function getBy( array $criteria );

    abstract public function getOneBy( array $criteria );

}