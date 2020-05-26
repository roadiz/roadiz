<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Viewers;

use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Models\DocumentInterface;

/**
 * Class DocumentViewer
 * @package RZ\Roadiz\Core\Viewers
 * @deprecated Use ChainRenderer
 */
class DocumentViewer extends AbstractDocumentViewer
{
    /**
     * @inheritDoc
     */
    protected function getDocumentAlt()
    {
        if ($this->document instanceof Document &&
            false !== $this->document->getDocumentTranslations()->first()) {
            return $this->document->getDocumentTranslations()->first()->getName();
        }

        return "";
    }

    /**
     * @inheritDoc
     */
    protected function getTemplatesBasePath()
    {
        return "documents";
    }

    /**
     * @inheritDoc
     */
    protected function getDocumentsByFilenames($filenames): array
    {
        return $this->entityManager
            ->getRepository(Document::class)
            ->findBy(["filename" => $filenames]);
    }

    /**
     * @inheritDoc
     *
     * @param array|string $filenames
     * @return Document|null
     */
    public function getOneDocumentByFilenames($filenames): ?DocumentInterface
    {
        return $this->entityManager
            ->getRepository(Document::class)
            ->findOneBy([
                "filename" => $filenames,
                "raw" => false,
            ]);
    }
}
