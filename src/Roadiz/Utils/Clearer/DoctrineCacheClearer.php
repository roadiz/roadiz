<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Clearer;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use RZ\Roadiz\Core\Kernel;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class DoctrineCacheClearer extends Clearer
{
    protected bool $recreateProxies;
    protected EntityManagerInterface $entityManager;
    protected Kernel $kernel;

    /**
     * @param EntityManagerInterface $entityManager
     * @param Kernel                 $kernel
     * @param bool                   $recreateProxies
     */
    public function __construct(EntityManagerInterface $entityManager, Kernel $kernel, bool $recreateProxies = true)
    {
        parent::__construct('');
        $this->entityManager = $entityManager;
        $this->kernel = $kernel;
        $this->recreateProxies = $recreateProxies;
    }

    /**
     * Test if database is configured and reachable.
     *
     * Useful if cache is being cleared outside of a VM or a docker env.
     *
     * @return bool
     */
    public function databaseAvailable(): bool
    {
        return null !== $this->entityManager &&
            ($this->entityManager->getConnection()->isConnected() || $this->entityManager->getConnection()->connect());
    }

    /**
     * @return bool
     */
    public function clear(): bool
    {
        if ($this->databaseAvailable()) {
            $conf = $this->entityManager->getConfiguration();
            $this->output .= $this->clearCacheDriver($conf->getResultCacheImpl(), 'result');
            $this->output .= $this->clearCacheDriver($conf->getHydrationCacheImpl(), 'hydratation');
            $this->output .= $this->clearCacheDriver($conf->getQueryCacheImpl(), 'query');
            $this->output .= $this->clearCacheDriver($conf->getMetadataCacheImpl(), 'metadata');
            if ($this->recreateProxies === true) {
                $this->recreateProxies();
            }
        }

        return true;
    }

    protected function clearCacheDriver(CacheProvider $cacheDriver = null, $description = ""): string
    {
        $output = '';
        if ($cacheDriver !== null) {
            $output = ucwords($description) . ' cache';
            if (!$cacheDriver->flushAll()) {
                if (!$cacheDriver->deleteAll()) {
                    $output .= ' <error>FAIL</error>';
                } else {
                    $output .= ' <info>DELETED</info>';
                }
            } else {
                $output .= ' <info>FLUSHED</info>';
            }
        }
        return $output . ' ';
    }

    /**
     * Recreate proxies files
     */
    protected function recreateProxies()
    {
        try {
            $fs = new Filesystem();
            $finder = new Finder();
            $conf = $this->entityManager->getConfiguration();
            $finder->files()->in($conf->getProxyDir());
            $fs->remove($finder);

            $meta = $this->entityManager->getMetadataFactory()->getAllMetadata();
            $proxyFactory = $this->entityManager->getProxyFactory();
            $proxyFactory->generateProxyClasses($meta, $conf->getProxyDir());
            $this->output .= 'Doctrine proxy classes has been recreated.';
        } catch (ORMException $exception) {
            $this->output .= '<error>Doctrine proxy can’t be recreated.</error>';
        }
    }
}
