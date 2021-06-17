<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\SearchEngine\Indexer;

use RZ\Roadiz\Core\Entities\Folder;
use Solarium\Exception\HttpException;

final class FolderIndexer extends DocumentIndexer
{
    public function index($id): void
    {
        try {
            $folder = $this->entityManager->find(Folder::class, $id);
            if (null === $folder) {
                return;
            }
            $update = $this->getSolr()->createUpdate();
            $documents = $folder->getDocuments();

            foreach ($documents as $document) {
                foreach ($document->getDocumentTranslations() as $documentTranslation) {
                    $solarium = $this->solariumFactory->createWithDocumentTranslation($documentTranslation);
                    $solarium->getDocumentFromIndex();
                    $solarium->update($update);
                }
            }
            $this->getSolr()->update($update);

            // then optimize
            $optimizeUpdate = $this->getSolr()->createUpdate();
            $optimizeUpdate->addOptimize(true, true, 5);
            $this->getSolr()->update($optimizeUpdate);
            // and commit
            $finalCommitUpdate = $this->getSolr()->createUpdate();
            $finalCommitUpdate->addCommit(true, true, false);
            $this->getSolr()->update($finalCommitUpdate);
        } catch (HttpException $exception) {
            $this->logger->error($exception->getMessage());
        }
    }

    public function delete($id): void
    {
        // Just reindex all linked documents to get rid of folder
        $this->index($id);
    }
}
