<?php

namespace RZ\Renzo\Core\Services;

use Pimple\Container;


use RZ\Renzo\Core\Kernel;

/**
 * Register configuration services for dependency injection container.
 */
class ConfigurationServiceProvider implements \Pimple\ServiceProviderInterface
{
    /**
     * @param Pimple\Container $container [description]
     */
    public function register(Container $container)
    {
        /*
         * Inject app config
         */
        $container['config'] = function ($c) {
            $configFile = RENZO_ROOT.'/conf/config.json';
            if (file_exists($configFile)) {
                return json_decode(file_get_contents($configFile), true);
            } else {
                return null;
            }
        };

        /*
         * Every path to parse to find doctrine entities
         */
        $container['entitiesPaths'] = function ($c) {
            if (isset($c['config']['entities'])) {
                return $c['config']['entities'];
            } else {
                return array(
                    "src/Renzo/Core/Entities",
                    "src/Renzo/Core/AbstractEntities",
                    "sources/GeneratedNodeSources"
                );
            }
        };
    }
}
