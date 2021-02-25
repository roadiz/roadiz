<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use RZ\Roadiz\Console\RoadizApplication;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Utils\Clearer\ClearerInterface;
use RZ\Roadiz\Utils\Clearer\DoctrineCacheClearer;
use RZ\Roadiz\Utils\Clearer\OPCacheClearer;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

final class SchemaUpdater
{
    private EntityManagerInterface $entityManager;
    private Kernel $kernel;
    private LoggerInterface $logger;

    /**
     * @param EntityManagerInterface $entityManager
     * @param Kernel $kernel
     * @param LoggerInterface $logger
     */
    public function __construct(EntityManagerInterface $entityManager, Kernel $kernel, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->kernel = $kernel;
        $this->logger = $logger;
    }

    public function clearMetadata(): void
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

    protected function createApplication(): Application
    {
        /*
         * Very important, when using standard-edition,
         * Kernel class is AppKernel or DevAppKernel.
         */
        /** @var class-string<Kernel> $kernelClass */
        $kernelClass = get_class($this->kernel);
        $application = new RoadizApplication(new $kernelClass('dev', true));
        $application->setAutoExit(false);
        return $application;
    }

    /**
     * Update database schema using doctrine migration.
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function updateSchema(): void
    {
        $this->clearMetadata();

        /*
         * Execute pending application migrations
         */
        $input = new ArrayInput([
            'command' => 'migrations:migrate',
            '--no-interaction' => true,
            '--allow-no-migration' => true
        ]);
        $output = new BufferedOutput();
        $exitCode = $this->createApplication()->run($input, $output);
        $content = $output->fetch();
        if ($exitCode === 0) {
            $this->logger->info('Executed pending migrations.', ['migration' => $content]);
        } else {
            throw new \RuntimeException('Migrations failed: ' . $content);
        }
    }

    /**
     * @throws \Exception
     */
    public function updateNodeTypesSchema(): void
    {
        /*
         * Execute pending application migrations
         */
        $input = new ArrayInput([
            'command' => 'migrations:migrate',
            '--no-interaction' => true,
            '--allow-no-migration' => true
        ]);
        $output = new BufferedOutput();
        $exitCode = $this->createApplication()->run($input, $output);
        $content = $output->fetch();
        if ($exitCode === 0) {
            $this->logger->info('Executed pending migrations.', ['migration' => $content]);
        } else {
            throw new \RuntimeException('Migrations failed: ' . $content);
        }

        /*
         * Update schema with new node-types
         * without creating any migration
         */
        $input = new ArrayInput([
            'command' => 'orm:schema-tool:update',
            '--dump-sql' => true,
            '--force' => true,
        ]);
        $output = new BufferedOutput();
        $exitCode = $this->createApplication()->run($input, $output);
        $content = $output->fetch();

        if ($exitCode === 0) {
            $this->logger->info('DB schema has been updated.', ['sql' => $content]);
        } else {
            throw new \RuntimeException('DB schema update failed: ' . $content);
        }
    }
}
