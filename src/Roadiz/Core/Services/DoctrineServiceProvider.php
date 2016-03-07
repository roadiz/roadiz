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
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Events;
use Doctrine\ORM\Tools\Setup;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\Roadiz\Core\Events\DataInheritanceEvent;

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
     * @param  array      $cacheConfig
     * @param  boolean    $isDevMode
     * @param  string|null $proxyDir
     * @param  string $environment
     * @return Cache
     */
    protected function getManuallyDefinedCache(
        array $cacheConfig,
        $isDevMode = false,
        $proxyDir = null,
        $environment = 'prod'
    ) {
        $proxyDir = $proxyDir ?: sys_get_temp_dir();

        if ($isDevMode === false) {
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
        if ($cache instanceof CacheProvider) {
            $cache->setNamespace("dc2_" . md5($proxyDir) . $environment . "_"); // to avoid collisions
        }

        return $cache;
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
        if ($container['config'] !== null &&
            isset($container['config']["doctrine"])) {
            $container['em.config'] = function ($c) {
                $cache = null;
                if (isset($c['config']['cacheDriver']) &&
                    !empty($c['config']['cacheDriver']['type'])) {
                    $cache = $this->getManuallyDefinedCache(
                        $c['config']['cacheDriver'],
                        $c['kernel']->isDevMode(),
                        $c['kernel']->getRootDir() . '/gen-src/Proxies',
                        $c['kernel']->getEnvironment()
                    );
                }

                $config = Setup::createAnnotationMetadataConfiguration(
                    $c['entitiesPaths'],
                    $c['kernel']->isDevMode(),
                    $c['kernel']->getRootDir() . '/gen-src/Proxies',
                    $cache,
                    false
                );
                $config->setProxyDir($c['kernel']->getRootDir() . '/gen-src/Proxies');
                $config->setProxyNamespace('Proxies');

                return $config;
            };

            $container['em'] = function ($c) {
                try {
                    $c['stopwatch']->start('initDoctrine');
                    $em = EntityManager::create($c['config']["doctrine"], $c['em.config']);
                    $evm = $em->getEventManager();

                    /*
                     * Create dynamic dicriminator map for our Node system
                     */
                    $prefix = isset($c['config']['doctrine']['prefix']) ? $c['config']['doctrine']['prefix'] : '';

                    $evm->addEventListener(
                        Events::loadClassMetadata,
                        new DataInheritanceEvent($prefix)
                    );

                    $resultCacheDriver = $em->getConfiguration()->getResultCacheImpl();
                    if ($resultCacheDriver !== null) {
                        $resultCacheDriver->setNamespace($c['config']["appNamespace"]);
                    }

                    $hydratationCacheDriver = $em->getConfiguration()->getHydrationCacheImpl();
                    if ($hydratationCacheDriver !== null) {
                        $hydratationCacheDriver->setNamespace($c['config']["appNamespace"]);
                    }

                    $queryCacheDriver = $em->getConfiguration()->getQueryCacheImpl();
                    if ($queryCacheDriver !== null) {
                        $queryCacheDriver->setNamespace($c['config']["appNamespace"]);
                    }

                    $metadataCacheDriver = $em->getConfiguration()->getMetadataCacheImpl();
                    if (null !== $metadataCacheDriver) {
                        $metadataCacheDriver->setNamespace($c['config']["appNamespace"]);
                    }

                    $c['stopwatch']->stop('initDoctrine');

                    return $em;
                } catch (\PDOException $e) {
                    $c['session']->getFlashBag()->add('error', $e->getMessage());
                    return null;
                }
            };
        }
        /*
         *
         */
        $container['nodesSourcesUrlCacheProvider'] = function ($c) {
            // clone existing cache to be able to vary namespace
            $cache = clone $c['em']->getConfiguration()->getMetadataCacheImpl();
            if ($cache instanceof CacheProvider) {
                $cache->setNamespace($c['config']["appNamespace"] . "_nsurls"); // to avoid collisions
            }

            return $cache;
        };

        return $container;
    }
}
