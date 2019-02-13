<?php
/**
 * Copyright © 2014, Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file DoctrineServiceProvider.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Services;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\Roadiz\Core\Events\CustomFormFieldLifeCycleSubscriber;
use RZ\Roadiz\Core\Events\DocumentLifeCycleSubscriber;
use RZ\Roadiz\Core\Events\FontLifeCycleSubscriber;
use RZ\Roadiz\Core\Events\LeafEntityLifeCycleSubscriber;
use RZ\Roadiz\Core\Events\NodesSourcesInheritanceSubscriber;
use RZ\Roadiz\Core\Events\TablePrefixSubscriber;
use RZ\Roadiz\Core\Events\UserLifeCycleSubscriber;
use RZ\Roadiz\Core\Exceptions\NoConfigurationFoundException;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Utils\Doctrine\CacheFactory;
use RZ\Roadiz\Utils\Doctrine\RoadizRepositoryFactory;
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

            if (isset($c['config']['entities'])) {
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
                /** @var Kernel $kernel */
                $kernel = $c['kernel'];
                /*
                 * Use ArrayCache if no cache type is explicitly defined.
                 */
                $cache = new ArrayCache();
                if ($c['config']['cacheDriver']['type'] !== null) {
                    $cache = CacheFactory::fromConfig(
                        $c['config']['cacheDriver'],
                        $kernel,
                        $c['config']["appNamespace"]
                    );
                }

                $proxyFolder = $kernel->getRootDir() . '/gen-src/Proxies';
                $config = Setup::createAnnotationMetadataConfiguration(
                    $c['doctrine.entities_paths'],
                    $kernel->isDevMode(),
                    $proxyFolder,
                    $cache,
                    false
                );
                $config->setProxyDir($proxyFolder);
                $config->setProxyNamespace('Proxies');
                /*
                 * Override default repository factory
                 * to inject Container into Doctrine repositories!
                 */
                $config->setRepositoryFactory(new RoadizRepositoryFactory($c, $kernel->isPreview()));

                return $config;
            } catch (NoConfigurationFoundException $e) {
                return null;
            }
        };

        $container['em'] = function (Container $c) {
            $c['stopwatch']->start('initDoctrine');

            try {
                /** @var Kernel $kernel */
                $kernel = $c['kernel'];
                /** @var EntityManager $em */
                $em = EntityManager::create($c['config']["doctrine"], $c['em.config']);
                $evm = $em->getEventManager();
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
            } catch (NoConfigurationFoundException $e) {
                $c['stopwatch']->stop('initDoctrine');
                $c['session']->getFlashBag()->add('error', $e->getMessage());
                return null;
            } catch (\PDOException $e) {
                $c['stopwatch']->stop('initDoctrine');
                $c['session']->getFlashBag()->add('error', $e->getMessage());
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
                new CustomFormFieldLifeCycleSubscriber($c),
                new LeafEntityLifeCycleSubscriber($c['factory.handler']),
            ];
        };

        /**
         * @param Container $c
         * @return CacheProvider
         */
        $container['nodesSourcesUrlCacheProvider'] = function ($c) {
            /** @var Kernel $kernel */
            $kernel = $c['kernel'];
            /*
             * Use ArrayCache if no cache type is explicitly defined.
             */
            $cache = new ArrayCache();
            if ($c['config']['cacheDriver']['type'] !== null &&
                !$kernel->isPreview() &&
                !$kernel->isDebug()) {
                $cache = CacheFactory::fromConfig(
                    $c['config']['cacheDriver'],
                    $kernel,
                    $c['config']["appNamespace"]
                );
            }
            $cache->setNamespace($cache->getNamespace() . "_nsurls_"); // to avoid collisions
            return $cache;
        };

        return $container;
    }
}
