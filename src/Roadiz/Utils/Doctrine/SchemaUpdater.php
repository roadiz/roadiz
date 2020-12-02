<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Doctrine;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Utils\Clearer\ClearerInterface;
use RZ\Roadiz\Utils\Clearer\DoctrineCacheClearer;
use RZ\Roadiz\Utils\Clearer\OPCacheClearer;

/**
 * SchemaUpdater.
 */
class SchemaUpdater
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var Kernel
     */
    private $kernel;

    /**
     * SchemaUpdater constructor.
     * @param EntityManager $entityManager
     * @param Kernel $kernel
     */
    public function __construct(EntityManager $entityManager, Kernel $kernel)
    {
        $this->entityManager = $entityManager;
        $this->kernel = $kernel;
    }

    /**
     *
     */
    public function clearMetadata()
    {
        $clearers = [
            new DoctrineCacheClearer($this->entityManager, $this->kernel),
            new OPCacheClearer(),
        ];

        /** @var ClearerInterface $clearer */
        foreach ($clearers as $clearer) {
            $clearer->clear();
        }
    }

    /**
     * Update database schema.
     *
     * @param boolean $delete Enable DELETE and DROP statements
     *
     * @return boolean
     * @throws \Doctrine\DBAL\DBALException
     */
    public function updateSchema($delete = false)
    {
        $this->clearMetadata();

        $tool = new SchemaTool($this->entityManager);
        $meta = $this->entityManager->getMetadataFactory()->getAllMetadata();

        $sql = $tool->getUpdateSchemaSql($meta, true);
        $deletions = [];

        foreach ($sql as $statement) {
            if (substr($statement, 0, 6) == 'DELETE' ||
                strpos($statement, 'DROP')) {
                $deletions[] = $statement;
            } else {
                $this->entityManager->getConnection()->exec($statement);
            }
        }

        if (true === $delete) {
            foreach ($deletions as $statement) {
                $this->entityManager->getConnection()->exec($statement);
            }
        }

        return true;
    }
}
