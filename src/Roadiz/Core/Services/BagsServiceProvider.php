<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Services;

use Doctrine\Persistence\ManagerRegistry;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\Roadiz\Core\Bags\NodeTypes;
use RZ\Roadiz\Core\Bags\Roles;
use RZ\Roadiz\Core\Bags\Settings;

/**
 * @package RZ\Roadiz\Core\Services
 */
class BagsServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container)
    {
        $container['settingsBag'] = function (Container $c) {
            return new Settings($c[ManagerRegistry::class]);
        };

        $container['rolesBag'] = function (Container $c) {
            return new Roles($c[ManagerRegistry::class]);
        };

        $container['nodeTypesBag'] = function (Container $c) {
            return new NodeTypes($c[ManagerRegistry::class]);
        };

        return $container;
    }
}
