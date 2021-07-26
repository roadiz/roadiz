<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Services;

use Doctrine\Persistence\ManagerRegistry;
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
    public function register(Container $pimple)
    {
        $pimple['nodeApi'] = function (Container $c) {
            return new NodeApi($c[ManagerRegistry::class]);
        };

        $pimple['nodeTypeApi'] = function (Container $c) {
            return new NodeTypeApi($c[ManagerRegistry::class]);
        };

        $pimple['nodeSourceApi'] = function (Container $c) {
            return new NodeSourceApi($c[ManagerRegistry::class]);
        };

        $pimple['tagApi'] = function (Container $c) {
            return new TagApi($c[ManagerRegistry::class]);
        };

        return $pimple;
    }
}
