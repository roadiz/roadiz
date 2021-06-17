<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\SearchEngine\Indexer;

interface Indexer
{
    public function reindexAll(): void;

    public function index($id): void;

    public function delete($id): void;
}
