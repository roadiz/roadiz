<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Services;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\Roadiz\Config\Configuration;
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
        $container['config.path'] = function (Container $c) {
            /** @var Kernel $kernel */
            $kernel = $c['kernel'];
            $configDir = $kernel->getRootDir() . '/conf';
            if ($kernel->getEnvironment() != 'prod') {
                $configName = 'config_' . $kernel->getEnvironment() . '.yml';
                if (file_exists($configDir . '/' . $configName)) {
                    return $configDir . '/' . $configName;
                }
            }

            return $configDir . '/config.yml';
        };

        $container[Configuration::class] = function (Container $c) {
            /** @var Kernel $kernel */
            $kernel = $c['kernel'];
            return new Configuration($kernel);
        };

        /*
         * Inject app config
         */
        $container['config.handler'] = function (Container $c) {
            /** @var Kernel $kernel */
            $kernel = $c['kernel'];
            return new YamlConfigurationHandler(
                $c[Configuration::class],
                $kernel->getCacheDir(),
                $kernel->isDebug(),
                $c['config.path']
            );
        };

        /*
         * Inject app config
         */
        $container['config'] = function (Container $c) {
            /** @var YamlConfigurationHandler $configuration */
            $configuration = $c['config.handler'];
            return $configuration->load();
        };

        return $container;
    }
}
