<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 *
 * @file EntityApiServiceProvider.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Services;

use Pimple\Container;
use RZ\Renzo\CMS\Utils\NodeApi;
use RZ\Renzo\CMS\Utils\NodeTypeApi;
use RZ\Renzo\CMS\Utils\NodeSourceApi;

/**
 * Register security services for dependency injection container.
 */
class EntityApiServiceProvider implements \Pimple\ServiceProviderInterface
{
    public function register(Container $container)
    {
        $container['nodeApi'] = function ($c) {
            return new NodeApi($c);
        };

        $container['nodeTypeApi'] = function ($c) {
            return new NodeTypeApi($c);
        };

        $container['nodeSourceApi'] = function ($c) {
            return new NodeSourceApi($c);
        };
    }
}