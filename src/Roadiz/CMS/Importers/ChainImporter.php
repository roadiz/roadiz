<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Importers;

class ChainImporter implements EntityImporterInterface
{
    private $importers = [];

    /**
     * @param array<EntityImporterInterface> $importers
     */
    public function __construct(array $importers)
    {
        $this->importers = $importers;
    }

    /**
     * @param EntityImporterInterface $entityImporter
     *
     * @return ChainImporter
     */
    public function addImporter(EntityImporterInterface $entityImporter): self
    {
        $this->importers[] = $entityImporter;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function supports(string $entityClass): bool
    {
        foreach ($this->importers as $importer) {
            if ($importer instanceof EntityImporterInterface && $importer->supports($entityClass)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function import(string $serializedData): bool
    {
        throw new \RuntimeException('You cannot call import method on ChainImporter, but importWithType method');
    }


    /**
     * @inheritDoc
     */
    public function importWithType(string $serializedData, string $entityClass): bool
    {
        foreach ($this->importers as $importer) {
            if ($importer instanceof EntityImporterInterface && $importer->supports($entityClass)) {
                return $importer->import($serializedData);
            }
        }
        return false;
    }
}
