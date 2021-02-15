<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Services;

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
    public function register(Container $container)
    {
        /**
         * @param Container $container
         *
         * @return NodeNameChecker
         */
        $container[NodeNamePolicyInterface::class] = function (Container $container) {
            return new NodeNameChecker($container['em']);
        };

        /**
         * @param Container $container
         * @return mixed
         * @deprecated 
         */
        $container['utils.nodeNameChecker'] = function (Container $container) {
            return $container[NodeNamePolicyInterface::class];
        };
        
        /**
         * @param Container $container
         *
         * @return UniqueNodeGenerator
         */
        $container['utils.uniqueNodeGenerator'] = function (Container $container) {
            return new UniqueNodeGenerator(
                $container['em'],
                $container[NodeNamePolicyInterface::class]
            );
        };
        /**
         * @param Container $container
         *
         * @return UniversalDataDuplicator
         */
        $container['utils.universalDataDuplicator'] = function (Container $container) {
            return new UniversalDataDuplicator($container['em']);
        };

        return $container;
    }
}
