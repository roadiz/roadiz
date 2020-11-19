<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Services;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\Roadiz\Config\ConfigurationHandlerInterface;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Utils\Log\LoggerFactory;
use RZ\Roadiz\Utils\Theme\StaticThemeResolver;
use RZ\Roadiz\Utils\Theme\ThemeGenerator;
use RZ\Roadiz\Utils\Theme\ThemeResolverInterface;

/**
 * Register Theme services for dependency injection container.
 */
class ThemeServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container)
    {
        /**
         * @param Container $c
         *
         * @return ThemeResolverInterface
         */
        $container['themeResolver'] = function (Container $c) {
            return new StaticThemeResolver($c['config'], $c['stopwatch'], $c['kernel']->isInstallMode());
        };

        $container['logger.themes'] = function (Container $c) {
            /** @var LoggerFactory $factory */
            $factory = $c[LoggerFactory::class];

            return $factory->createLogger('themes', 'themes');
        };

        $container[ThemeGenerator::class] = function (Container $c) {
            /** @var Kernel $kernel */
            $kernel = $c['kernel'];
            return new ThemeGenerator(
                $kernel->getProjectDir(),
                $kernel->getPublicDir(),
                $kernel->getCacheDir(),
                $c[ConfigurationHandlerInterface::class],
                $c['logger.themes']
            );
        };

        return $container;
    }
}
