<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Services;

use Doctrine\Persistence\ManagerRegistry;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\Roadiz\CMS\Importers\ChainImporter;
use RZ\Roadiz\CMS\Importers\GroupsImporter;
use RZ\Roadiz\CMS\Importers\NodesImporter;
use RZ\Roadiz\CMS\Importers\NodeTypesImporter;
use RZ\Roadiz\CMS\Importers\RolesImporter;
use RZ\Roadiz\CMS\Importers\SettingsImporter;
use RZ\Roadiz\CMS\Importers\TagsImporter;

class ImporterServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritDoc
     */
    public function register(Container $container)
    {
        $container[ChainImporter::class] = $container->factory(function (Container $c) {
            return new ChainImporter([
                $c[GroupsImporter::class],
                $c[NodesImporter::class],
                $c[NodeTypesImporter::class],
                $c[RolesImporter::class],
                $c[SettingsImporter::class],
                $c[TagsImporter::class],
            ]);
        });

        $container[GroupsImporter::class] = $container->factory(function (Container $c) {
            return new GroupsImporter($c);
        });
        $container[NodesImporter::class] = $container->factory(function (Container $c) {
            return new NodesImporter($c[ManagerRegistry::class]);
        });
        $container[NodeTypesImporter::class] = $container->factory(function (Container $c) {
            return new NodeTypesImporter($c);
        });
        $container[RolesImporter::class] = $container->factory(function (Container $c) {
            return new RolesImporter($c);
        });
        $container[SettingsImporter::class] = $container->factory(function (Container $c) {
            return new SettingsImporter($c[ManagerRegistry::class], $c['serializer']);
        });
        $container[TagsImporter::class] = $container->factory(function (Container $c) {
            return new TagsImporter($c);
        });
    }
}
