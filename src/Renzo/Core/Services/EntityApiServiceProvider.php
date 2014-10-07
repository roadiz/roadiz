<?php

namespace RZ\Renzo\Core\Services;

use Pimple\Container;
use RZ\Renzo\Core\Kernel;
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
            $nodeApi = new NodeApi();
            return $nodeApi;
        };

        $container['nodeTypeApi'] = function ($c) {
            $nodeApi = new NodeTypeApi();
            return $nodeApi;
        };

        $container['nodeSourceApi'] = function ($c) {
            $nodeApi = new NodeSourceApi();
            return $nodeApi;
        };
    }
}