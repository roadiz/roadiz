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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command line utils for managing Cache from terminal.
 */
class CacheInfosCommand extends Command
{
    private $entityManager;
    private $nsCacheHelper;

    protected function configure()
    {
        $this->setName('cache:infos')
            ->setDescription('Get cache informations.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $text = "";

        $this->entityManager = $this->getHelperSet()->get('em')->getEntityManager();
        $this->nsCacheHelper = $this->getHelperSet()->get('ns-cache');

        $text .= $this->getInformations();

        $output->writeln($text);
    }

    public function getInformations()
    {
        $text = '';

        $cacheDriver = $this->entityManager->getConfiguration()->getResultCacheImpl();
        if (null !== $cacheDriver) {
            $text .= "<info>Result cache driver:</info> " . get_class($cacheDriver) . PHP_EOL;
            $text .= "    <info>Namespace:</info> " . $cacheDriver->getNamespace() . PHP_EOL;
        }

        $cacheDriver = $this->entityManager->getConfiguration()->getHydrationCacheImpl();
        if (null !== $cacheDriver) {
            $text .= "<info>Hydratation cache driver:</info> " . get_class($cacheDriver) . PHP_EOL;
            $text .= "    <info>Namespace:</info> " . $cacheDriver->getNamespace() . PHP_EOL;
        }

        $cacheDriver = $this->entityManager->getConfiguration()->getQueryCacheImpl();
        if (null !== $cacheDriver) {
            $text .= "<info>Query cache driver:</info> " . get_class($cacheDriver) . PHP_EOL;
            $text .= "    <info>Namespace:</info> " . $cacheDriver->getNamespace() . PHP_EOL;
        }

        $cacheDriver = $this->entityManager->getConfiguration()->getMetadataCacheImpl();
        if (null !== $cacheDriver) {
            $text .= "<info>Metadata cache driver:</info> " . get_class($cacheDriver) . PHP_EOL;
            $text .= "    <info>Namespace:</info> " . $cacheDriver->getNamespace() . PHP_EOL;
        }

        if (null !== $this->nsCacheHelper->getCacheProvider()) {
            $nsCache = $this->nsCacheHelper->getCacheProvider();
            $text .= "<info>Node-sources URLs cache driver:</info> " . get_class($nsCache) . PHP_EOL;
            $text .= "    <info>Namespace:</info> " . $nsCache->getNamespace() . PHP_EOL;
        }

        return $text;
    }
}
