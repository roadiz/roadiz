<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\SearchEngine\Indexer;

use RZ\Roadiz\Core\Entities\NodesSources;
use Solarium\Exception\HttpException;
use Solarium\Plugin\BufferedAdd\BufferedAdd;
use Solarium\QueryType\Update\Query\Query as UpdateQuery;

class NodesSourcesIndexer extends AbstractIndexer
{
    public function index($id): void
    {
        $update = $this->getSolr()->createUpdate();
        $this->indexNodeSource(
            $this->entityManager->find(NodesSources::class, $id),
            $update
        );
        $update->addCommit(true, true, false);
        $this->getSolr()->update($update);
    }

    protected function indexNodeSource(?NodesSources $nodeSource, UpdateQuery $update): void
    {
        if (null !== $nodeSource) {
            try {
                $solrSource = $this->solariumFactory->createWithNodesSources($nodeSource);
                $solrSource->getDocumentFromIndex();
                $solrSource->update($update);
            } catch (HttpException $exception) {
                $this->logger->error($exception->getMessage());
            }
        }
    }

    public function delete($id): void
    {
        $this->deleteNodeSource($this->entityManager->find(NodesSources::class, $id));
    }

    protected function deleteNodeSource(?NodesSources $nodeSource): void
    {
        if (null !== $nodeSource) {
            try {
                $solrSource = $this->solariumFactory->createWithNodesSources($nodeSource);
                $solrSource->getDocumentFromIndex();
                $solrSource->removeAndCommit();
            } catch (HttpException $exception) {
                $this->logger->error($exception->getMessage());
            }
        }
    }

    /**
     * Delete Solr index and loop over every NodesSources to index them again.
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function reindexAll(): void
    {
        $update = $this->getSolr()->createUpdate();
        /*
         * Use buffered insertion
         */
        /** @var BufferedAdd $buffer */
        $buffer = $this->getSolr()->getPlugin('bufferedadd');
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

        if (null !== $this->io) {
            $this->io->progressStart($countQuery->getSingleScalarResult());
        }

        while (($row = $iterableResult->next()) !== false) {
            $solarium = $this->solariumFactory->createWithNodesSources($row[0]);
            $solarium->createEmptyDocument($update);
            $solarium->index();
            $buffer->addDocument($solarium->getDocument());

            if (null !== $this->io) {
                $this->io->progressAdvance();
            }
        }

        $buffer->flush();

        // optimize the index
        $this->optimizeSolr();

        if (null !== $this->io) {
            $this->io->progressFinish();
        }
    }
}
