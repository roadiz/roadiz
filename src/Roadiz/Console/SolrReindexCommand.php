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
 * @file SolrReindexCommand.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Console;

use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\SearchEngine\SolariumDocument;
use RZ\Roadiz\Core\SearchEngine\SolariumNodeSource;
use Solarium\Plugin\BufferedAdd\BufferedAdd;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
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

        $text = "";

        if (null !== $this->solr) {
            if (true === $this->getHelper('solr')->ready()) {
                $confirmation = new ConfirmationQuestion(
                    '<question>Are you sure to reindex your Node and Document database?</question> [y/N]: ',
                    false
                );
                if (!$input->isInteractive() ||
                    $this->questionHelper->ask(
                        $input,
                        $output,
                        $confirmation
                    )) {
                    $stopwatch = new Stopwatch();
                    $stopwatch->start('global');
                    // Empty first
                    $this->emptySolr($output);

                    if ($input->getOption('documents')) {
                        $this->reindexDocuments($output);
                    } elseif ($input->getOption('nodes')) {
                        $this->reindexNodeSources($output);
                    } else {
                        $this->reindexDocuments($output);
                        $this->reindexNodeSources($output);
                    }

                    $stopwatch->stop('global');

                    $duration = $stopwatch->getEvent('global')->getDuration();

                    $text = PHP_EOL . sprintf('<info>Node and document database has been re-indexed in %.2d ms.</info>', $duration) . PHP_EOL;
                }
            } else {
                $text .= '<error>Solr search engine server does not respond…</error>' . PHP_EOL;
                $text .= 'See your config.yml file to correct your Solr connexion settings.' . PHP_EOL;
            }
        } else {
            $text .= $this->displayBasicConfig();
        }

        $output->writeln($text);
    }

    /**
     * Delete Solr index and loop over every NodesSources to index them again.
     *
     * @param OutputInterface $output
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function reindexNodeSources(OutputInterface $output)
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

        $progress = new ProgressBar($output, $countQuery->getSingleScalarResult());
        $progress->setFormat('verbose');
        $progress->start();

        while (($row = $iterableResult->next()) !== false) {
            $solarium = new SolariumNodeSource(
                $row[0],
                $this->solr,
                $this->getHelper('kernel')->getKernel()->get('dispatcher'),
                $this->getHelper('handlerFactory')->getHandlerFactory(),
                $this->getHelper('logger')->getLogger()
            );
            $solarium->createEmptyDocument($update);
            $solarium->index();
            $buffer->addDocument($solarium->getDocument());
            $progress->advance();

            $this->entityManager->detach($row[0]);
        }

        $buffer->flush();

        // optimize the index
        $this->optimizeSolr($output);
        $progress->finish();
    }

    /**
     * Delete Solr index and loop over every Documents to index them again.
     *
     * @param OutputInterface $output
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function reindexDocuments(OutputInterface $output)
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

        $progress = new ProgressBar($output, $countQuery->getSingleScalarResult());
        $progress->setFormat('verbose');
        $progress->start();

        while (($row = $iterableResult->next()) !== false) {
            $solarium = new SolariumDocument(
                $row[0],
                $this->solr,
                $this->getHelper('logger')->getLogger()
            );
            $solarium->createEmptyDocument($update);
            $solarium->index();
            foreach ($solarium->getDocuments() as $document) {
                $buffer->addDocument($document);
            }
            $progress->advance();

            $this->entityManager->detach($row[0]);
        }

        $buffer->flush();

        // optimize the index
        $this->optimizeSolr($output);
        $progress->finish();
    }
}
