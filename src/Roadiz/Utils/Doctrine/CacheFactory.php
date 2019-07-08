<?php
/**
 * Copyright (c) 2019. Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
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
 * @file CacheFactory.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace RZ\Roadiz\Utils\Doctrine;

use Doctrine\Common\Cache\ApcuCache;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Cache\FilesystemCache;
use Doctrine\Common\Cache\MemcacheCache;
use Doctrine\Common\Cache\MemcachedCache;
use Doctrine\Common\Cache\PhpFileCache;
use Doctrine\Common\Cache\RedisCache;
use Doctrine\Common\Cache\XcacheCache;
use RZ\Roadiz\Core\Kernel;

class CacheFactory
{
    /**
     * Get cache driver according to config.yml entry.
     *
     * Logic from Doctrine setup method
     * https://github.com/doctrine/doctrine2/blob/master/lib/Doctrine/ORM/Tools/Setup.php#L122
     *
     * @param array  $cacheConfig
     * @param Kernel $kernel
     * @param string $namespace
     *
     * @return CacheProvider
     */
    public static function fromConfig(array $cacheConfig, Kernel $kernel, $namespace = 'dc2'): CacheProvider
    {
        if ($kernel->isProdMode()) {
            if (extension_loaded('apcu') &&
                !empty($cacheConfig['type']) &&
                $cacheConfig['type'] == 'apcu'
            ) {
                $cache = new ApcuCache();
            } elseif (extension_loaded('apc') &&
                !empty($cacheConfig['type']) &&
                $cacheConfig['type'] == 'apc'
            ) {
                $cache = new ApcuCache();
            } elseif (!empty($cacheConfig['type']) &&
                $cacheConfig['type'] == 'php'
            ) {
                $cache = new PhpFileCache($kernel->getCacheDir().'/doctrine');
            } elseif (!empty($cacheConfig['type']) &&
                $cacheConfig['type'] == 'file'
            ) {
                $cache = new FilesystemCache($kernel->getCacheDir().'/doctrine');
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
        $cache->setNamespace(static::getNamespace($namespace, $kernel->isPreview(), $kernel->getEnvironment()));
        return $cache;
    }

    /**
     * @param string $namespace
     * @param bool $isPreview
     * @param string $environment
     * @return string
     */
    public static function getNamespace($namespace = 'dc2', $isPreview = false, $environment = 'prod'): string
    {
        $namespace = $namespace . "_" . $environment . "_";
        if ($isPreview) {
            $namespace .= 'preview_';
        }

        return $namespace;
    }
}
