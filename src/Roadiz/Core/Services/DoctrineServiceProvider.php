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
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Events;
use Doctrine\ORM\Tools\Setup;
use Pimple\Container;
use RZ\Roadiz\Core\Events\DataInheritanceEvent;

/**
 * Register Doctrine services for dependency injection container.
 */
class DoctrineServiceProvider implements \Pimple\ServiceProviderInterface
{
    /**
     * Initialize Doctrine entity manager in DI container.
     *
     * This method can be called from InstallApp after updating
     * doctrine configuration.
     *
     * @param Pimple\Container $container [description]
     */
    public function register(Container $container)
    {
        if ($container['config'] !== null &&
            isset($container['config']["doctrine"])) {
            $container['em.config'] = function ($c) {
                $config = Setup::createAnnotationMetadataConfiguration(
                    $c['entitiesPaths'],
                    (boolean) $c['config']['devMode'],
                    ROADIZ_ROOT . '/gen-src/Proxies',
                    null,
                    false
                );

                $config->setProxyDir(ROADIZ_ROOT . '/gen-src/Proxies');
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
                    $evm->addEventListener(Events::loadClassMetadata, new DataInheritanceEvent());

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
         * logic from Doctrine setup method
         *
         * https://github.com/doctrine/doctrine2/blob/master/lib/Doctrine/ORM/Tools/Setup.php#L122
         */
        $container['nodesSourcesUrlCacheProvider'] = function ($c) {

            if (true === (boolean) $c['config']['devMode']) {
                $cache = new ArrayCache();
            } else {
                if (extension_loaded('apc')) {
                    $cache = new \Doctrine\Common\Cache\ApcCache();
                } elseif (extension_loaded('xcache')) {
                    $cache = new \Doctrine\Common\Cache\XcacheCache();
                } elseif (extension_loaded('memcache')) {
                    $memcache = new \Memcache();
                    $memcache->connect('127.0.0.1');
                    $cache = new \Doctrine\Common\Cache\MemcacheCache();
                    $cache->setMemcache($memcache);
                } elseif (extension_loaded('redis')) {
                    $redis = new \Redis();
                    $redis->connect('127.0.0.1');
                    $cache = new \Doctrine\Common\Cache\RedisCache();
                    $cache->setRedis($redis);
                } else {
                    $cache = new ArrayCache();
                }
            }


            if ($cache instanceof CacheProvider) {
                $cache->setNamespace($c['config']["appNamespace"] . "_nsurls"); // to avoid collisions
            }

            return $cache;
        };

        return $container;
    }
}
