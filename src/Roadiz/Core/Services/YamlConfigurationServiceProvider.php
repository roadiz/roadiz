<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Services;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\Roadiz\Config\YamlConfigurationHandler;
use RZ\Roadiz\Core\Kernel;

/**
 * Register configuration services for dependency injection container.
 */
class YamlConfigurationServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Container $container
     * @return Container
     */
    public function register(Container $container)
    {
        $container['config.path'] = function (Container $container) {
            /** @var Kernel $kernel */
            $kernel = $container['kernel'];
            $configDir = $kernel->getRootDir() . '/conf';
            if ($kernel->getEnvironment() != 'prod') {
                $configName = 'config_' . $kernel->getEnvironment() . '.yml';
                if (file_exists($configDir . '/' . $configName)) {
                    return $configDir . '/' . $configName;
                }
            }

            return $configDir . '/config.yml';
        };

        /*
         * Inject app config
         */
        $container['config.handler'] = function (Container $container) {
            /** @var Kernel $kernel */
            $kernel = $container['kernel'];
            return new YamlConfigurationHandler(
                $kernel->getCacheDir(),
                $kernel->isDebug(),
                $container['config.path']
            );
        };

        /*
         * Inject app config
         */
        $container['config'] = function (Container $container) {
            /** @var YamlConfigurationHandler $configuration */
            $configuration = $container['config.handler'];
            return $configuration->load();
        };

        return $container;
    }
}
