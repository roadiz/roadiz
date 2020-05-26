<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Importers;

interface EntityImporterInterface
{
    /**
     * @param string $entityClass
     *
     * @return bool
     */
    public function supports(string $entityClass): bool;

    /**
     * @param string $serializedData
     *
     * @return bool
     */
    public function import(string $serializedData): bool;
}
