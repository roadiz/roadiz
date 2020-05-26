<?php
declare(strict_types=1);

namespace RZ\Roadiz\Document;

use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Models\DocumentInterface;

final class DocumentFinder implements DocumentFinderInterface
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /**
     * DocumentFinder constructor.
     *
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @inheritDoc
     */
    public function findAllByFilenames(array $fileNames)
    {
        return $this->entityManager
            ->getRepository(Document::class)
            ->findBy([
                "filename" => $fileNames,
                "raw" => false,
            ]);
    }

    /**
     * @inheritDoc
     */
    public function findOneByFilenames(array $fileNames): ?DocumentInterface
    {
        return $this->entityManager
            ->getRepository(Document::class)
            ->findOneBy([
                "filename" => $fileNames,
                "raw" => false,
            ]);
    }
}
