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

use Symfony\Component\Finder\Finder;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Filesystem\Filesystem;

/**
 * DoctrineCacheClearer.
 */
class DoctrineCacheClearer extends Clearer
{
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function clear()
    {
        // Empty result cache
        $cacheDriver = $this->entityManager->getConfiguration()->getResultCacheImpl();
        if ($cacheDriver !== null) {
            $this->output .= 'Doctrine result cache: '.$cacheDriver->getNamespace().' — ';
            $this->output .= $cacheDriver->deleteAll() ? '<info>OK</info>' : '<info>FAIL</info>';
            $this->output .= PHP_EOL;
        }

        // Empty hydratation cache
        $cacheDriver = $this->entityManager->getConfiguration()->getHydrationCacheImpl();
        if ($cacheDriver !== null) {
            $this->output .= 'Doctrine hydratation cache: '.$cacheDriver->getNamespace().' — ';
            $this->output .= $cacheDriver->deleteAll() ? '<info>OK</info>' : '<info>FAIL</info>';
            $this->output .= PHP_EOL;
        }

        // Empty query cache
        $cacheDriver = $this->entityManager->getConfiguration()->getQueryCacheImpl();
        if ($cacheDriver !== null) {
            $this->output .= 'Doctrine query cache: '.$cacheDriver->getNamespace().' — ';
            $this->output .= $cacheDriver->deleteAll() ? '<info>OK</info>' : '<info>FAIL</info>';
            $this->output .= PHP_EOL;
        }

        // Empty metadata cache
        $cacheDriver = $this->entityManager->getConfiguration()->getMetadataCacheImpl();
        if ($cacheDriver !== null) {
            $this->output .= 'Doctrine metadata cache: '.$cacheDriver->getNamespace().' — ';
            $this->output .= $cacheDriver->deleteAll() ? '<info>OK</info>' : '<info>FAIL</info>';
            $this->output .= PHP_EOL;
        }

        /*
         * Recreate proxies files
         */
        $fs = new Filesystem();
        $finder = new Finder();
        $finder->files()->in(ROADIZ_ROOT . '/gen-src/Proxies');
        $fs->remove($finder);

        $meta = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $proxyFactory = $this->entityManager->getProxyFactory();
        $proxyFactory->generateProxyClasses($meta, ROADIZ_ROOT . '/gen-src/Proxies');
        $this->output .= 'Doctrine proxy classes has been purged.'.PHP_EOL;

        return true;
    }
}
