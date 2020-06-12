<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Document;

use RZ\Roadiz\Core\Entities\Document;

/**
 * Create private documents from UploadedFile.
 *
 * Factory methods do not flush, only persist in order to use it in loops.
 *
 * @package RZ\Roadiz\Utils\Document
 */
class PrivateDocumentFactory extends AbstractDocumentFactory
{
    /**
     * @inheritDoc
     */
    protected function createDocument()
    {
        $document = new Document();
        $document->setPrivate(true);
        return $document;
    }
}
