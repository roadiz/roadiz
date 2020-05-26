<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Doctrine;

use Doctrine\Common\Cache\ApcuCache;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Cache\FilesystemCache;
use Doctrine\Common\Cache\MemcachedCache;
use Doctrine\Common\Cache\PhpFileCache;
use Doctrine\Common\Cache\RedisCache;
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
            } elseif (extension_loaded('memcached') &&
                class_exists('\Memcached') &&
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
                class_exists('\Redis') &&
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
