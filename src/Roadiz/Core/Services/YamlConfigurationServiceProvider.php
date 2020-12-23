<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Services;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\Roadiz\Config\Configuration;
use RZ\Roadiz\Config\ConfigurationHandlerInterface;
use RZ\Roadiz\Config\DotEnvConfigurationHandler;
use RZ\Roadiz\Config\Loader\ConfigurationLoader;
use RZ\Roadiz\Config\Loader\YamlConfigurationLoader;
use RZ\Roadiz\Core\Kernel;
use Symfony\Component\Config\ConfigCache;

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
            if ($kernel->getEnvironment() !== 'prod') {
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

        $container[ConfigCache::class] = function (Container $c) {
            /** @var Kernel $kernel */
            $kernel = $c['kernel'];
            return new ConfigCache(
                $kernel->getCacheDir() . '/configuration.php',
                $kernel->isDebug()
            );
        };

        $container[ConfigurationLoader::class] = function (Container $c) {
            return new YamlConfigurationLoader();
        };

        /*
         * Inject app config
         */
        $container[ConfigurationHandlerInterface::class] = function (Container $c) {
            return new DotEnvConfigurationHandler(
                $c[Configuration::class],
                $c['config.path'],
                $c[ConfigurationLoader::class],
                $c[ConfigCache::class]
            );
        };

        /*
         * Inject app config
         */
        $container['config'] = function (Container $c) {
            $configuration = $c[ConfigurationHandlerInterface::class];
            return $configuration->load();
        };

        return $container;
    }
}
