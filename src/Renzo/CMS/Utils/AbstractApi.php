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

use Pimple\Container;

/**
 *
 */
abstract class AbstractApi
{

    /*
     * DI container
     */
    protected $container;

    /**
     * @param Pimple\Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    abstract public function getRepository();

    abstract public function getBy(array $criteria);

    abstract public function getOneBy(array $criteria);
}
