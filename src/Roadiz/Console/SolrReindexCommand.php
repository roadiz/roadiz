<?php
declare(strict_types=1);

namespace RZ\Roadiz\Console;

use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\SearchEngine\SolariumDocumentTranslation;
use RZ\Roadiz\Core\SearchEngine\SolariumFactoryInterface;
use RZ\Roadiz\Core\SearchEngine\SolariumNodeSource;
use Solarium\Plugin\BufferedAdd\BufferedAdd;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Command line utils for managing nodes from terminal.
 */
class SolrReindexCommand extends SolrCommand implements ThemeAwareCommandInterface
{
    protected $questionHelper;

    protected function configure()
    {
        $this->setName('solr:reindex')
            ->setDescription('Reindex Solr search engine index')
            ->addOption('nodes', null, InputOption::VALUE_NONE, 'Reindex with only nodes.')
            ->addOption('documents', null, InputOption::VALUE_NONE, 'Reindex with only documents.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->questionHelper = $this->getHelper('question');
        $this->entityManager = $this->getHelper('entityManager')->getEntityManager();
        $this->solr = $this->getHelper('solr')->getSolr();
        $this->io = new SymfonyStyle($input, $output);

        if (null !== $this->solr) {
            if (true === $this->getHelper('solr')->ready()) {
                if ($this->io->confirm(
                    'Are you sure to reindex your Node and Document database?',
                    !$input->isInteractive()
                )) {
                    $stopwatch = new Stopwatch();
                    $stopwatch->start('global');

                    if ($input->getOption('documents')) {
                        // Empty first
                        $this->emptySolr(SolariumDocumentTranslation::DOCUMENT_TYPE);
                        $this->reindexDocuments();

                        $stopwatch->stop('global');
                        $duration = $stopwatch->getEvent('global')->getDuration();
                        $this->io->success(sprintf('Document database has been re-indexed in %.2d ms.', $duration));
                    } elseif ($input->getOption('nodes')) {
                        // Empty first
                        $this->emptySolr(SolariumNodeSource::DOCUMENT_TYPE);
                        $this->reindexNodeSources();

                        $stopwatch->stop('global');
                        $duration = $stopwatch->getEvent('global')->getDuration();
                        $this->io->success(sprintf('Node database has been re-indexed in %.2d ms.', $duration));
                    } else {
                        // Empty first
                        $this->emptySolr();
                        $this->reindexDocuments();
                        $this->reindexNodeSources();

                        $stopwatch->stop('global');
                        $duration = $stopwatch->getEvent('global')->getDuration();
                        $this->io->success(sprintf('Node and document database has been re-indexed in %.2d ms.', $duration));
                    }
                }
            } else {
                $this->io->error('Solr search engine server does not respond…');
                $this->io->note('See your config.yml file to correct your Solr connexion settings.');
                return 1;
            }
        } else {
            $this->displayBasicConfig();
        }
        return 0;
    }

    /**
     * Delete Solr index and loop over every NodesSources to index them again.
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function reindexNodeSources()
    {
        $update = $this->solr->createUpdate();
        /*
         * Use buffered insertion
         */
        /** @var BufferedAdd $buffer */
        $buffer = $this->solr->getPlugin('bufferedadd');
        $buffer->setBufferSize(100);

        $countQuery = $this->entityManager
            ->getRepository(NodesSources::class)
            ->createQueryBuilder('ns')
            ->select('count(ns)')
            ->innerJoin('ns.node', 'n')
            ->getQuery();
        $q = $this->entityManager
            ->getRepository(NodesSources::class)
            ->createQueryBuilder('ns')
            ->addSelect('n')
            ->innerJoin('ns.node', 'n')
            ->getQuery();
        $iterableResult = $q->iterate();

        $this->io->progressStart($countQuery->getSingleScalarResult());
        /** @var SolariumFactoryInterface $solariumFactory */
        $solariumFactory = $this->getHelper('kernel')->getKernel()->get(SolariumFactoryInterface::class);

        while (($row = $iterableResult->next()) !== false) {
            $solarium = $solariumFactory->createWithNodesSources($row[0]);
            $solarium->createEmptyDocument($update);
            $solarium->index();
            $buffer->addDocument($solarium->getDocument());
            $this->io->progressAdvance();
        }

        $buffer->flush();

        // optimize the index
        $this->optimizeSolr();
        $this->io->progressFinish();
    }

    /**
     * Delete Solr index and loop over every Documents to index them again.
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function reindexDocuments()
    {
        $update = $this->solr->createUpdate();
        /*
         * Use buffered insertion
         */
        /** @var BufferedAdd $buffer */
        $buffer = $this->solr->getPlugin('bufferedadd');
        $buffer->setBufferSize(100);

        $countQuery = $this->entityManager
            ->getRepository(Document::class)
            ->createQueryBuilder('d')
            ->select('count(d)')
            ->getQuery();
        $q = $this->entityManager->getRepository(Document::class)
            ->createQueryBuilder('d')
            ->getQuery();
        $iterableResult = $q->iterate();

        $this->io->progressStart($countQuery->getSingleScalarResult());
        /** @var SolariumFactoryInterface $solariumFactory */
        $solariumFactory = $this->getHelper('kernel')->getKernel()->get(SolariumFactoryInterface::class);

        while (($row = $iterableResult->next()) !== false) {
            $solarium = $solariumFactory->createWithDocument($row[0]);
            $solarium->createEmptyDocument($update);
            $solarium->index();
            foreach ($solarium->getDocuments() as $document) {
                $buffer->addDocument($document);
            }
            $this->io->progressAdvance();
        }

        $buffer->flush();

        // optimize the index
        $this->optimizeSolr();
        $this->io->progressFinish();
    }
}
