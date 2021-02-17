<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Document;

use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Models\DocumentInterface;

/**
 * Create documents from UploadedFile.
 *
 * Factory methods do not flush, only persist in order to use it in loops.
 *
 * @package RZ\Roadiz\Utils\Document
 */
class DocumentFactory extends AbstractDocumentFactory
{
    /**
     * @inheritDoc
     */
    protected function createDocument(): DocumentInterface
    {
        return new Document();
    }
}
