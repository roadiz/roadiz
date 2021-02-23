<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Services;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\EventSubscriber;
use Doctrine\Migrations\Configuration\EntityManager\ExistingEntityManager;
use Doctrine\Migrations\Configuration\Migration\ConfigurationArray;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Tools\ResolveTargetEntityListener;
use Doctrine\ORM\Tools\Setup;
use Gedmo\Loggable\LoggableListener;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\Roadiz\Attribute\Model\AttributeGroupInterface;
use RZ\Roadiz\Core\Entities\AttributeGroup;
use RZ\Roadiz\Core\Events\CustomFormFieldLifeCycleSubscriber;
use RZ\Roadiz\Core\Events\DocumentLifeCycleSubscriber;
use RZ\Roadiz\Core\Events\FontLifeCycleSubscriber;
use RZ\Roadiz\Core\Events\LeafEntityLifeCycleSubscriber;
use RZ\Roadiz\Core\Events\NodesSourcesInheritanceSubscriber;
use RZ\Roadiz\Core\Events\SettingLifeCycleSubscriber;
use RZ\Roadiz\Core\Events\TablePrefixSubscriber;
use RZ\Roadiz\Core\Events\UserLifeCycleSubscriber;
use RZ\Roadiz\Core\Exceptions\NoConfigurationFoundException;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Preview\PreviewResolverInterface;
use RZ\Roadiz\Utils\Doctrine\CacheFactory;
use RZ\Roadiz\Utils\Doctrine\Loggable\UserLoggableListener;
use RZ\Roadiz\Utils\Doctrine\RoadizRepositoryFactory;
use RZ\Roadiz\Utils\Doctrine\SchemaUpdater;
use RZ\Roadiz\Utils\Theme\ThemeInfo;
use RZ\Roadiz\Utils\Theme\ThemeResolverInterface;
use Scienta\DoctrineJsonFunctions\Query\AST\Functions\Mysql\JsonContains;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Register Doctrine services for dependency injection container.
 */
class DoctrineServiceProvider implements ServiceProviderInterface
{
    /**
     * Initialize Doctrine entity manager in DI container.
     *
     * This method can be called from InstallApp after updating
     * doctrine configuration.
     *
     * @param Container $container [description]
     * @return Container
     */
    public function register(Container $container)
    {
        $container['doctrine.relative_entities_paths'] = function (Container $container) {
            /** @var Kernel $kernel */
            $kernel = $container['kernel'];
            if ($kernel->getRootDir() !== $kernel->getPublicDir()) {
                /*
                 * Standard edition
                 */
                $relPaths = [
                    "../vendor/roadiz/roadiz/src/Roadiz/Core/Entities",
                    "../vendor/roadiz/models/src/Roadiz/Core/AbstractEntities",
                    "gen-src/GeneratedNodeSources",
                ];
            } else {
                /*
                 * Source edition
                 */
                $relPaths = [
                    "src/Roadiz/Core/Entities",
                    "vendor/roadiz/models/src/Roadiz/Core/AbstractEntities",
                    "gen-src/GeneratedNodeSources",
                ];
            }

            if (isset($container['config']['entities'])) {
                $relPaths = array_merge($relPaths, $container['config']['entities']);
            }

            return array_filter(array_unique($relPaths));
        };
        /*
         * Every path to parse to find doctrine entities
         */
        $container['doctrine.entities_paths'] = function (Container $container) {
            /*
             * We need to work with absolute paths.
             */
            /** @var Kernel $kernel */
            $kernel = $container['kernel'];
            $fs = new Filesystem();
            $absPaths = [];
            foreach ($container['doctrine.relative_entities_paths'] as $relPath) {
                $absolutePath = $kernel->getRootDir() . DIRECTORY_SEPARATOR . $relPath;
                if ($fs->exists($absolutePath)) {
                    $absPaths[] = $kernel->getRootDir() . DIRECTORY_SEPARATOR . $relPath;
                }
            }

            return $absPaths;
        };

        $container['em.config'] = function (Container $c) {
            try {
                AnnotationRegistry::registerLoader('class_exists');
                /** @var Kernel $kernel */
                $kernel = $c['kernel'];
                $cache = $c[CacheProvider::class];

                $proxyFolder = $kernel->getRootDir() . '/gen-src/Proxies';
                $config = Setup::createAnnotationMetadataConfiguration(
                    $c['doctrine.entities_paths'],
                    $kernel->isDevMode(),
                    $proxyFolder,
                    $cache,
                    false
                );
                /*
                 * Create a cached annotation driver with configured cache driver.
                 */
                $config->setMetadataDriverImpl($c[AnnotationDriver::class]);
                $config->setProxyDir($proxyFolder);
                $config->setProxyNamespace('Proxies');
                $config->addCustomStringFunction(JsonContains::FUNCTION_NAME, JsonContains::class);
                /*
                 * Override default repository factory
                 * to inject Container into Doctrine repositories!
                 */

                /** @var PreviewResolverInterface $previewResolver */
                $previewResolver = $c[PreviewResolverInterface::class];
                $config->setRepositoryFactory(new RoadizRepositoryFactory($c, $previewResolver));

                return $config;
            } catch (NoConfigurationFoundException $e) {
                return null;
            }
        };

        /*
         * Alias with FQN interface
         */
        $container[EntityManagerInterface::class] = function (Container $c) {
            return $c['em'];
        };

        $container[ResolveTargetEntityListener::class] = function (Container $c) {
            $resolveListener = new ResolveTargetEntityListener();
            $resolveListener->addResolveTargetEntity(
                AttributeGroupInterface::class,
                AttributeGroup::class,
                []
            );
            return $resolveListener;
        };

        $container['em'] = function (Container $c) {
            $c['stopwatch']->start('initDoctrine');

            try {
                /** @var Kernel $kernel */
                $kernel = $c['kernel'];
                $em = EntityManager::create($c['config']["doctrine"], $c['em.config']);
                $evm = $em->getEventManager();

                // Add the ResolveTargetEntityListener
                $evm->addEventListener(Events::loadClassMetadata, $c[ResolveTargetEntityListener::class]);
                /*
                 * Inject doctrine event subscribers for
                 * a service to be able to add new ones from themes.
                 */
                foreach ($c['em.eventSubscribers'] as $eventSubscriber) {
                    $evm->addEventSubscriber($eventSubscriber);
                }

                if (!$kernel->isInstallMode() && $kernel->isDebug()) {
                    $em->getConnection()->getConfiguration()->setSQLLogger($c['doctrine.debugstack']);
                }

                $c['stopwatch']->stop('initDoctrine');
                return $em;
            } catch (\PDOException $e) {
                $c['stopwatch']->stop('initDoctrine');
                return null;
            }
        };

        /**
         * @param Container $c
         * @return EventSubscriber[] Event subscribers for Entity manager.
         */
        $container['em.eventSubscribers'] = function (Container $c) {
            $prefix = isset($c['config']['doctrine']['prefix']) ? $c['config']['doctrine']['prefix'] : '';
            return [
                new NodesSourcesInheritanceSubscriber($c),
                new TablePrefixSubscriber($prefix),
                new FontLifeCycleSubscriber($c),
                new DocumentLifeCycleSubscriber($c['kernel']),
                new UserLifeCycleSubscriber($c),
                new SettingLifeCycleSubscriber($c),
                new CustomFormFieldLifeCycleSubscriber($c),
                new LeafEntityLifeCycleSubscriber($c['factory.handler']),
                $c[LoggableListener::class],
            ];
        };

        /**
         * @param Container $c
         * @return CacheProvider
         */
        $container['nodesSourcesUrlCacheProvider'] = function (Container $c) {
            $cache = $c[CacheProvider::class];
            $cache->setNamespace($cache->getNamespace() . "_nsurls_"); // to avoid collisions
            return $cache;
        };

        $container[CacheProvider::class] = $container->factory(function (Container $c) {
            /** @var Kernel $kernel */
            $kernel = $c['kernel'];
            return CacheFactory::fromConfig(
                $c['config']['cacheDriver'],
                $kernel->getEnvironment(),
                $kernel->getCacheDir(),
                $c['config']["appNamespace"]
            );
        });

        $container[LoggableListener::class] = function (Container $c) {
            $loggableListener = new UserLoggableListener();
            $loggableListener->setAnnotationReader($c[CachedReader::class]);
            $loggableListener->setUsername('anonymous');
            $loggableListener->setUser(null);
            return $loggableListener;
        };

        $container[AnnotationDriver::class] = function (Container $c) {
            return new AnnotationDriver(
                new CachedReader(new AnnotationReader(), new ArrayCache()),
                $c['doctrine.entities_paths']
            );
        };

        $container[SchemaUpdater::class] = function (Container $c) {
            return new SchemaUpdater(
                $c['em'],
                $c['kernel'],
                $c['logger.doctrine']
            );
        };

        $container[CachedReader::class] = function (Container $c) {
            return new CachedReader(new AnnotationReader(), $c[CacheProvider::class]);
        };

        $container['doctrine.migrations_paths'] = function (Container $c) {
            /** @var ThemeResolverInterface $themeResolver */
            $themeResolver = $c['themeResolver'];
            $paths = [
                'RZ\Roadiz\Migrations' => realpath(dirname(__DIR__) . '/../Migrations'),
            ];

            foreach ($themeResolver->getFrontendThemes() as $frontendTheme) {
                $themeInfo = new ThemeInfo($frontendTheme->getClassName(), $c['kernel']->getProjectDir());
                if (\file_exists($themeInfo->getThemePath() . '/Migrations')) {
                    $themeNamespace = $themeInfo->getThemeReflectionClass()->getNamespaceName();
                    $paths[$themeNamespace . '\Migrations'] = $themeInfo->getThemePath() . '/Migrations';
                }
            }

            $appMigrationsPath = $c['kernel']->getRootDir() . '/migrations';
            if (\file_exists($appMigrationsPath)) {
                $paths['App\Migrations'] = $appMigrationsPath;
            }

            return array_reverse($paths);
        };

        $container[DependencyFactory::class] = function (Container $c) {
            return DependencyFactory::fromEntityManager(
                new ConfigurationArray([
                    'migrations_paths' => $c['doctrine.migrations_paths']
                ]),
                new ExistingEntityManager($c['em']),
                $c['logger.cli']
            );
        };

        return $container;
    }
}
