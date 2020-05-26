<?php
declare(strict_types=1);

namespace RZ\Roadiz\Console;

use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\SearchEngine\SolariumFactoryInterface;
use Solarium\Plugin\BufferedAdd\BufferedAdd;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
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
                $confirmation = new ConfirmationQuestion(
                    '<question>Are you sure to reindex your Node and Document database?</question>',
                    false
                );
                if (!$input->isInteractive() ||
                    $this->io->askQuestion($confirmation)
                ) {
                    $stopwatch = new Stopwatch();
                    $stopwatch->start('global');
                    // Empty first
                    $this->emptySolr();

                    if ($input->getOption('documents')) {
                        $this->reindexDocuments();
                    } elseif ($input->getOption('nodes')) {
                        $this->reindexNodeSources();
                    } else {
                        $this->reindexDocuments();
                        $this->reindexNodeSources();
                    }

                    $stopwatch->stop('global');
                    $duration = $stopwatch->getEvent('global')->getDuration();
                    $this->io->success(sprintf('Node and document database has been re-indexed in %.2d ms.', $duration));
                }
            } else {
                $this->io->error('Solr search engine server does not respond…');
                $this->io->note('See your config.yml file to correct your Solr connexion settings.');
                return 1;
            }
        } else {
            $this->io->note($this->displayBasicConfig());
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
