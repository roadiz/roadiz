<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\SearchEngine\Indexer;

use RZ\Roadiz\Core\Entities\Document;
use Solarium\Exception\HttpException;
use Solarium\Plugin\BufferedAdd\BufferedAdd;

class DocumentIndexer extends AbstractIndexer
{
    public function index($id): void
    {
        $document = $this->entityManager->find(Document::class, $id);
        if (null !== $document) {
            try {
                foreach ($document->getDocumentTranslations() as $documentTranslation) {
                    $solarium = $this->solariumFactory->createWithDocumentTranslation($documentTranslation);
                    $solarium->getDocumentFromIndex();
                    $solarium->updateAndCommit();
                }
            } catch (HttpException $exception) {
                $this->logger->error($exception->getMessage());
            }
        }
    }

    public function delete($id): void
    {
        $document = $this->entityManager->find(Document::class, $id);
        if (null !== $document) {
            try {
                foreach ($document->getDocumentTranslations() as $documentTranslation) {
                    $solarium = $this->solariumFactory->createWithDocumentTranslation($documentTranslation);
                    $solarium->getDocumentFromIndex();
                    $solarium->removeAndCommit();
                }
            } catch (HttpException $exception) {
                $this->logger->error($exception->getMessage());
            }
        }
    }

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
            ->getRepository(Document::class)
            ->createQueryBuilder('d')
            ->select('count(d)')
            ->getQuery();
        $q = $this->entityManager->getRepository(Document::class)
            ->createQueryBuilder('d')
            ->getQuery();
        $iterableResult = $q->iterate();

        if (null !== $this->io) {
            $this->io->progressStart($countQuery->getSingleScalarResult());
        }

        while (($row = $iterableResult->next()) !== false) {
            $solarium = $this->solariumFactory->createWithDocument($row[0]);
            $solarium->createEmptyDocument($update);
            $solarium->index();
            foreach ($solarium->getDocuments() as $document) {
                $buffer->addDocument($document);
            }
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
