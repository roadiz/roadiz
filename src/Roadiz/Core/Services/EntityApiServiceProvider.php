<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Services;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\Roadiz\CMS\Utils\NodeApi;
use RZ\Roadiz\CMS\Utils\NodeSourceApi;
use RZ\Roadiz\CMS\Utils\NodeTypeApi;
use RZ\Roadiz\CMS\Utils\TagApi;

/**
 * Register security services for dependency injection container.
 */
class EntityApiServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container)
    {
        $container['nodeApi'] = function (Container $c) {
            return new NodeApi($c);
        };

        $container['nodeTypeApi'] = function (Container $c) {
            return new NodeTypeApi($c);
        };

        $container['nodeSourceApi'] = function (Container $c) {
            return new NodeSourceApi($c);
        };

        $container['tagApi'] = function (Container $c) {
            return new TagApi($c);
        };

        return $container;
    }
}
