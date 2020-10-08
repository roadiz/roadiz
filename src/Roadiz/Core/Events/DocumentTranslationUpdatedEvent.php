<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Events;

use RZ\Roadiz\Core\Entities\DocumentTranslation;
use RZ\Roadiz\Core\Models\DocumentInterface;

final class DocumentTranslationUpdatedEvent extends FilterDocumentEvent
{
    /**
     * @var DocumentTranslation|null
     */
    protected $documentTranslation;

    public function __construct(DocumentInterface $document, ?DocumentTranslation $documentTranslation = null)
    {
        parent::__construct($document);
        $this->documentTranslation = $documentTranslation;
    }

    /**
     * @return DocumentTranslation|null
     */
    public function getDocumentTranslation(): ?DocumentTranslation
    {
        return $this->documentTranslation;
    }
}
