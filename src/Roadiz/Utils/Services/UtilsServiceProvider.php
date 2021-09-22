<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Services;

use Doctrine\Persistence\ManagerRegistry;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\Roadiz\Utils\Node\NodeNameChecker;
use RZ\Roadiz\Utils\Node\NodeNamePolicyInterface;
use RZ\Roadiz\Utils\Node\UniqueNodeGenerator;
use RZ\Roadiz\Utils\Node\UniversalDataDuplicator;

class UtilsServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritDoc
     */
    public function register(Container $pimple): void
    {
        /**
         * @return NodeNameChecker
         */
        $pimple[NodeNamePolicyInterface::class] = function (Container $c) {
            return new NodeNameChecker(
                $c[ManagerRegistry::class],
                (bool) $c['settingsBag']->get('use_typed_node_names', true)
            );
        };

        /**
         * @return mixed
         * @deprecated
         */
        $pimple['utils.nodeNameChecker'] = function (Container $c) {
            return $c[NodeNamePolicyInterface::class];
        };

        /**
         * @return UniqueNodeGenerator
         */
        $pimple['utils.uniqueNodeGenerator'] = function (Container $c) {
            return new UniqueNodeGenerator(
                $c[ManagerRegistry::class],
                $c[NodeNamePolicyInterface::class]
            );
        };
        /**
         * @return UniversalDataDuplicator
         */
        $pimple['utils.universalDataDuplicator'] = function (Container $c) {
            return new UniversalDataDuplicator($c[ManagerRegistry::class]);
        };
    }
}
