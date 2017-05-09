<?php
/**
 * Copyright © 2015, Ambroise Maupate and Julien Blanchet
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
 * @file DoctrineCacheClearer.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Utils\Clearer;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Core\Kernel;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * DoctrineCacheClearer.
 */
class DoctrineCacheClearer extends Clearer
{
    private $entityManager;
    /**
     * @var Kernel
     */
    private $kernel;

    /**
     * DoctrineCacheClearer constructor.
     *
     * @param EntityManager $entityManager
     * @param Kernel $kernel
     */
    public function __construct(EntityManager $entityManager, Kernel $kernel)
    {
        parent::__construct('');
        $this->entityManager = $entityManager;
        $this->kernel = $kernel;
    }

    /**
     * Test if database is configured and reachable.
     *
     * Useful if cache is being cleared outside of a VM or a docker env.
     *
     * @return bool
     */
    public function databaseAvailable()
    {
        return null !== $this->entityManager &&
            ($this->entityManager->getConnection()->isConnected() || $this->entityManager->getConnection()->connect());
    }

    /**
     * @return bool
     */
    public function clear()
    {
        if ($this->databaseAvailable()) {
            $conf = $this->entityManager->getConfiguration();
            $this->clearCacheDriver($conf->getResultCacheImpl(), 'result');
            $this->clearCacheDriver($conf->getHydrationCacheImpl(), 'hydratation');
            $this->clearCacheDriver($conf->getQueryCacheImpl(), 'query');
            $this->clearCacheDriver($conf->getMetadataCacheImpl(), 'metadata');
            $this->recreateProxies();
        }

        return true;
    }

    protected function clearCacheDriver(CacheProvider $cacheDriver = null, $description = "")
    {
        if ($cacheDriver !== null) {
            $this->output .= 'Doctrine ' . $description . ' cache: ' . $cacheDriver->getNamespace() . ' — ';
            $this->output .= $cacheDriver->deleteAll() ? '<info>OK</info>' : '<info>FAIL</info>';
        }
    }

    /**
     * Recreate proxies files
     */
    protected function recreateProxies()
    {
        $fs = new Filesystem();
        $finder = new Finder();
        $proxyFolder = $this->kernel->getRootDir() . '/gen-src/Proxies';
        $finder->files()->in($proxyFolder);
        $fs->remove($finder);

        $meta = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $proxyFactory = $this->entityManager->getProxyFactory();
        $proxyFactory->generateProxyClasses($meta, $proxyFolder);
        $this->output .= 'Doctrine proxy classes has been recreated.';
    }
}
