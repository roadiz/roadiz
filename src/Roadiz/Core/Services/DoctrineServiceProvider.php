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

use Doctrine\Common\Cache\ApcuCache;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Cache\MemcacheCache;
use Doctrine\Common\Cache\MemcachedCache;
use Doctrine\Common\Cache\RedisCache;
use Doctrine\Common\Cache\XcacheCache;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Events;
use Doctrine\ORM\Tools\Setup;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\Roadiz\Core\Events\DataInheritanceEvent;
use RZ\Roadiz\Core\Events\DocumentLifeCycleSubscriber;
use RZ\Roadiz\Core\Events\FontLifeCycleSubscriber;
use RZ\Roadiz\Core\Events\UserLifeCycleSubscriber;
use RZ\Roadiz\Core\Exceptions\NoConfigurationFoundException;
use RZ\Roadiz\Core\Kernel;

/**
 * Register Doctrine services for dependency injection container.
 */
class DoctrineServiceProvider implements ServiceProviderInterface
{
    /**
     * Get cache driver according to config.yml entry.
     *
     * Logic from Doctrine setup method
     * https://github.com/doctrine/doctrine2/blob/master/lib/Doctrine/ORM/Tools/Setup.php#L122
     *
     * @param array $cacheConfig
     * @param string $namespace
     * @param bool $isPreview
     * @param string $environment
     * @return Cache
     */
    protected function getManuallyDefinedCache(
        array $cacheConfig,
        $namespace = 'dc2',
        $isPreview = false,
        $environment = 'prod'
    ) {
        if ($environment === 'prod') {
            if (extension_loaded('apc') &&
                !empty($cacheConfig['type']) &&
                $cacheConfig['type'] == 'apc'
            ) {
                $cache = new ApcuCache();
            } elseif (extension_loaded('xcache') &&
                !empty($cacheConfig['type']) &&
                $cacheConfig['type'] == 'xcache'
            ) {
                $cache = new XcacheCache();
            } elseif (extension_loaded('memcache') &&
                !empty($cacheConfig['type']) &&
                $cacheConfig['type'] == 'memcache'
            ) {
                $memcache = new \Memcache();
                $host = !empty($cacheConfig['host']) ? $cacheConfig['host'] : '127.0.0.1';
                if (!empty($cacheConfig['port'])) {
                    $memcache->connect($host, $cacheConfig['port']);
                } else {
                    $memcache->connect($host);
                }
                $cache = new MemcacheCache();
                $cache->setMemcache($memcache);
            } elseif (extension_loaded('memcached') &&
                !empty($cacheConfig['type']) &&
                $cacheConfig['type'] == 'memcached'
            ) {
                $memcached = new \Memcached();
                $host = !empty($cacheConfig['host']) ? $cacheConfig['host'] : '127.0.0.1';
                $port = !empty($cacheConfig['port']) ? $cacheConfig['port'] : 11211;
                $memcached->addServer($host, $port);

                $cache = new MemcachedCache();
                $cache->setMemcached($memcached);
            } elseif (extension_loaded('redis') &&
                !empty($cacheConfig['type']) &&
                $cacheConfig['type'] == 'redis'
            ) {
                $redis = new \Redis();
                $host = !empty($cacheConfig['host']) ? $cacheConfig['host'] : '127.0.0.1';
                if (!empty($cacheConfig['port'])) {
                    $redis->connect($host, $cacheConfig['port']);
                } else {
                    $redis->connect($host);
                }
                $cache = new RedisCache();
                $cache->setRedis($redis);
            } else {
                $cache = new ArrayCache();
            }
        } else {
            $cache = new ArrayCache();
        }
        /*
         * Set namespace
         */
        if ($cache instanceof CacheProvider) {
            $cache->setNamespace($this->getNamespace($namespace, $isPreview, $environment));
        }

        return $cache;
    }

    /**
     * @param string $namespace
     * @param bool $isPreview
     * @param string $environment
     * @return string
     */
    public function getNamespace($namespace = 'dc2', $isPreview = false, $environment = 'prod')
    {
        $namespace = $namespace . "_" . $environment . "_";
        if ($isPreview) {
            $namespace .= 'preview_';
        }

        return $namespace;
    }

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
        $container['em.config'] = function (Container $c) {
            try {
                /** @var Kernel $kernel */
                $kernel = $c['kernel'];
                $cache = null;
                if (isset($c['config']['cacheDriver']) &&
                    !empty($c['config']['cacheDriver']['type'])) {
                    $cache = $this->getManuallyDefinedCache(
                        $c['config']['cacheDriver'],
                        $c['config']["appNamespace"],
                        $kernel->isPreview(),
                        $kernel->getEnvironment()
                    );
                }

                $proxyFolder = $kernel->getRootDir() . '/gen-src/Proxies';
                $config = Setup::createAnnotationMetadataConfiguration(
                    $c['entitiesPaths'],
                    $kernel->isDevMode(),
                    $proxyFolder,
                    $cache,
                    false
                );
                $config->setProxyDir($proxyFolder);
                $config->setProxyNamespace('Proxies');

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

                $prefix = isset($c['config']['doctrine']['prefix']) ? $c['config']['doctrine']['prefix'] : '';

                /** @var CacheProvider $resultCacheDriver */
                $resultCacheDriver = $em->getConfiguration()->getResultCacheImpl();
                if ($resultCacheDriver !== null) {
                    $resultCacheDriver->setNamespace($this->getNamespace(
                        $c['config']["appNamespace"],
                        $kernel->isPreview(),
                        $kernel->getEnvironment()
                    ));
                }
                /** @var CacheProvider $hydratationCacheDriver */
                $hydratationCacheDriver = $em->getConfiguration()->getHydrationCacheImpl();
                if ($hydratationCacheDriver !== null) {
                    $hydratationCacheDriver->setNamespace($this->getNamespace(
                        $c['config']["appNamespace"],
                        $kernel->isPreview(),
                        $kernel->getEnvironment()
                    ));
                }
                /** @var CacheProvider $queryCacheDriver */
                $queryCacheDriver = $em->getConfiguration()->getQueryCacheImpl();
                if ($queryCacheDriver !== null) {
                    $queryCacheDriver->setNamespace($this->getNamespace(
                        $c['config']["appNamespace"],
                        $kernel->isPreview(),
                        $kernel->getEnvironment()
                    ));
                }
                /** @var CacheProvider $metadataCacheDriver */
                $metadataCacheDriver = $em->getConfiguration()->getMetadataCacheImpl();
                if (null !== $metadataCacheDriver) {
                    $metadataCacheDriver->setNamespace($this->getNamespace(
                        $c['config']["appNamespace"],
                        $kernel->isPreview(),
                        $kernel->getEnvironment()
                    ));
                }

                /*
                 * Create dynamic discriminator map for our Node system
                 */
                $evm->addEventListener(
                    Events::loadClassMetadata,
                    new DataInheritanceEvent($c, $prefix)
                );

                /*
                 * Inject doctrine event subscribers for
                 * a service to be able to add new ones from themes.
                 */
                foreach ($c['em.eventSubscribers'] as $eventSubscriber) {
                    $evm->addEventSubscriber($eventSubscriber);
                }

                if ($c['kernel']->isDebug()) {
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
        $container['em.eventSubscribers'] = function ($c) {
            return [
                new FontLifeCycleSubscriber($c),
                new DocumentLifeCycleSubscriber($c),
                new UserLifeCycleSubscriber($c),
            ];
        };

        /*
         *
         */
        $container['nodesSourcesUrlCacheProvider'] = function ($c) {
            if (null !== $c['em'] && $c['kernel']->getEnvironment() != 'test') {
                // clone existing cache to be able to vary namespace
                $cache = clone $c['em']->getConfiguration()->getMetadataCacheImpl();
                if ($cache instanceof CacheProvider) {
                    $cache->setNamespace($cache->getNamespace() . "nsurls_"); // to avoid collisions
                }
                return $cache;
            } else {
                return new ArrayCache();
            }
        };

        return $container;
    }
}
