<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\SearchEngine\Indexer;

use Doctrine\ORM\QueryBuilder;
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
            $this->managerRegistry->getRepository(NodesSources::class)->find($id),
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
        $this->deleteNodeSource($this->managerRegistry->getRepository(NodesSources::class)->find($id));
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
     * Overridable
     *
     * @return QueryBuilder
     */
    protected function getAllQueryBuilder(): QueryBuilder
    {
        return $this->managerRegistry
            ->getRepository(NodesSources::class)
            ->createQueryBuilder('ns')
            ->innerJoin('ns.node', 'n');
    }

    /**
     * Loop over every NodesSources to index them again.
     *
     * @param int $batchCount Split reindex span to several batches.
     * @param int $batchNumber Execute reindex on a specific batch.
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function reindexAll(int $batchCount = 1, int $batchNumber = 0): void
    {
        $update = $this->getSolr()->createUpdate();
        /*
         * Use buffered insertion
         */
        /** @var BufferedAdd $buffer */
        $buffer = $this->getSolr()->getPlugin('bufferedadd');
        $buffer->setBufferSize(100);

        $countQuery = $this->getAllQueryBuilder()
            ->select('count(ns)')
            ->getQuery();
        $count = $countQuery->getSingleScalarResult();

        $baseQb = $this->getAllQueryBuilder()->addSelect('n');
        if ($batchCount > 1) {
            $limit = round($count/$batchCount);
            $offset = $batchNumber * $limit;
            if ($batchNumber === $batchCount - 1) {
                $limit = $count - $offset;
                $baseQb->setMaxResults($limit)->setFirstResult($offset);
                if (null !== $this->io) {
                    $this->io->note('Batch mode enabled (last): Limit to ' . $limit . ', offset from ' . $offset);
                }
            } else {
                $baseQb->setMaxResults($limit)->setFirstResult($offset);
                if (null !== $this->io) {
                    $this->io->note('Batch mode enabled: Limit to ' . $limit . ', offset from ' . $offset);
                }
            }
            $count = $limit;
        }
        $q = $baseQb->getQuery();
        $iterableResult = $q->iterate();

        if (null !== $this->io) {
            $this->io->progressStart($count);
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
