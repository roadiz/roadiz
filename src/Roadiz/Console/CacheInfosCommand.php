<?php
/**
 * Copyright © 2016, Ambroise Maupate and Julien Blanchet
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
 * @file CacheInfosCommand.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Console;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command line utils for managing Cache from terminal.
 */
class CacheInfosCommand extends Command
{
    /**
     * @var EntityManager
     */
    private $entityManager;
    private $nsCacheHelper;

    protected function configure()
    {
        $this->setName('cache:info')
            ->setDescription('Get cache information.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $this->entityManager = $this->getHelper('entityManager')->getEntityManager();
        $this->nsCacheHelper = $this->getHelper('ns-cache');

        $io->listing($this->getInformation());
    }

    public function getInformation(): array
    {
        $outputs = [];
        /** @var CacheProvider $cacheDriver */
        $cacheDriver = $this->entityManager->getConfiguration()->getResultCacheImpl();
        if (null !== $cacheDriver) {
            $outputs[] = implode(', ', [
                '<info>Result cache driver:</info> ' . get_class($cacheDriver),
                '<info>Namespace:</info> ' . $cacheDriver->getNamespace()
            ]);
        }

        $cacheDriver = $this->entityManager->getConfiguration()->getHydrationCacheImpl();
        if (null !== $cacheDriver) {
            $outputs[] = implode(', ', [
                '<info>Hydratation cache driver:</info> ' . get_class($cacheDriver),
                '<info>Namespace:</info> ' . $cacheDriver->getNamespace()
            ]);
        }

        $cacheDriver = $this->entityManager->getConfiguration()->getQueryCacheImpl();
        if (null !== $cacheDriver) {
            $outputs[] = implode(', ', [
                '<info>Query cache driver:</info> ' . get_class($cacheDriver),
                 '<info>Namespace:</info> ' . $cacheDriver->getNamespace()
            ]);
        }

        $cacheDriver = $this->entityManager->getConfiguration()->getMetadataCacheImpl();
        if (null !== $cacheDriver) {
            $outputs[] = implode(', ', [
                '<info>Metadata cache driver:</info> ' . get_class($cacheDriver),
                 '<info>Namespace:</info> ' . $cacheDriver->getNamespace()
            ]);
        }

        if (null !== $this->nsCacheHelper->getCacheProvider()) {
            /** @var CacheProvider $nsCache */
            $nsCache = $this->nsCacheHelper->getCacheProvider();
            $outputs[] = implode(', ', [
                '<info>Node-sources URLs cache driver:</info> ' . get_class($nsCache),
                 '<info>Namespace:</info> ' . $nsCache->getNamespace()
            ]);
        }

        return $outputs;
    }
}
