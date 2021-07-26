<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Clearer;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use RZ\Roadiz\Core\Kernel;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class DoctrineCacheClearer extends Clearer
{
    protected bool $recreateProxies;
    protected ManagerRegistry $managerRegistry;
    protected Kernel $kernel;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param Kernel                 $kernel
     * @param bool                   $recreateProxies
     */
    public function __construct(ManagerRegistry $managerRegistry, Kernel $kernel, bool $recreateProxies = true)
    {
        parent::__construct('');
        $this->managerRegistry = $managerRegistry;
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
        return null !== $this->managerRegistry &&
            ($this->managerRegistry->getConnection()->isConnected() || $this->managerRegistry->getConnection()->connect());
    }

    /**
     * @return bool
     */
    public function clear(): bool
    {
        if ($this->databaseAvailable()) {
            $conf = $this->managerRegistry->getManager()->getConfiguration();
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
            $conf = $this->managerRegistry->getManager()->getConfiguration();
            $finder->files()->in($conf->getProxyDir());
            $fs->remove($finder);

            $meta = $this->managerRegistry->getManager()->getMetadataFactory()->getAllMetadata();
            $proxyFactory = $this->managerRegistry->getManager()->getProxyFactory();
            $proxyFactory->generateProxyClasses($meta, $conf->getProxyDir());
            $this->output .= 'Doctrine proxy classes has been recreated.';
        } catch (ORMException $exception) {
            $this->output .= '<error>Doctrine proxy can’t be recreated.</error>';
        }
    }
}
