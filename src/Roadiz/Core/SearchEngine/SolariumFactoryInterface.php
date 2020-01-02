<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\SearchEngine;

use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Entities\DocumentTranslation;
use RZ\Roadiz\Core\Entities\NodesSources;

interface SolariumFactoryInterface
{
    public function createWithDocument(Document $document): SolariumDocument;

    public function createWithDocumentTranslation(DocumentTranslation $documentTranslation): SolariumDocumentTranslation;

    public function createWithNodesSources(NodesSources $nodeSource): SolariumNodeSource;
}
