<?php
declare(strict_types=1);

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
        $this->entityManager = $this->getHelper('doctrine')->getEntityManager();
        $this->nsCacheHelper = $this->getHelper('ns-cache');

        $io->listing($this->getInformation());
        return 0;
    }

    public function getInformation(): array
    {
        $outputs = [];
        /** @var CacheProvider $cacheDriver */
        $cacheDriver = $this->entityManager->getConfiguration()->getResultCacheImpl();
        if (null !== $cacheDriver) {
            $outputs[] = implode(', ', [
                '<info>Result cache driver:</info> ' . get_class($cacheDriver),
            ]);
        }

        $cacheDriver = $this->entityManager->getConfiguration()->getHydrationCacheImpl();
        if (null !== $cacheDriver) {
            $outputs[] = implode(', ', [
                '<info>Hydratation cache driver:</info> ' . get_class($cacheDriver),
            ]);
        }

        $cacheDriver = $this->entityManager->getConfiguration()->getQueryCacheImpl();
        if (null !== $cacheDriver) {
            $outputs[] = implode(', ', [
                '<info>Query cache driver:</info> ' . get_class($cacheDriver),
            ]);
        }

        $cacheDriver = $this->entityManager->getConfiguration()->getMetadataCacheImpl();
        if (null !== $cacheDriver) {
            $outputs[] = implode(', ', [
                '<info>Metadata cache driver:</info> ' . get_class($cacheDriver),
            ]);
        }

        if (null !== $this->nsCacheHelper->getCacheProvider()) {
            /** @var CacheProvider $nsCache */
            $nsCache = $this->nsCacheHelper->getCacheProvider();
            $outputs[] = implode(', ', [
                '<info>Node-sources URLs cache driver:</info> ' . get_class($nsCache),
            ]);
        }

        return $outputs;
    }
}
