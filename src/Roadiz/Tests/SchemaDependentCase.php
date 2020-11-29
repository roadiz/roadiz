<?php
declare(strict_types=1);

namespace RZ\Roadiz\Tests;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\RiskyTestError;
use RZ\Roadiz\Console\RoadizApplication;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Kernel;
use Symfony\Component\Console\Input\StringInput;

/**
 * Class SchemaDependentCase for UnitTest which need EntityManager.
 *
 * Be careful, these tests must be executed on a clear database! Or all data will be lost.
 *
 * @package RZ\Roadiz\Tests
 */
abstract class SchemaDependentCase extends KernelDependentCase
{
    /**
     * @throws \Doctrine\ORM\Tools\ToolsException
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $em = static::getManager();
        $schemaTool = new SchemaTool($em);

        // Drop and recreate tables for all entities
        $dropSQL = $schemaTool->getDropDatabaseSQL();
        if (count($dropSQL) > 0) {
            throw new RiskyTestError('Test database is not empty! Do not execute tests on a running Roadiz db.');
        }

        static::runCommand('orm:schema-tool:create');
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        static::runCommand('cache:clear');
    }

    public static function tearDownAfterClass(): void
    {
        static::runCommand('orm:schema-tool:drop --force');

        parent::tearDownAfterClass();
    }

    /**
     * @param string      $title
     * @param Translation $translation
     *
     * @return Node
     * @throws \Doctrine\ORM\ORMException
     */
    protected static function createNode(string $title, Translation $translation): Node
    {
        $node = new Node();
        $node->setNodeName($title);
        $node->setVisible(true);
        static::getManager()->persist($node);

        $ns = new NodesSources($node, $translation);
        $ns->setTitle($title);
        $ns->setPublishedAt(new \DateTime());
        static::getManager()->persist($ns);

        $node->addNodeSources($ns);

        return $node;
    }

    /**
     * @return EntityManager
     */
    public static function getManager()
    {
        return static::$kernel->get('em');
    }

    /**
     * @param string $command
     * @throws \Exception
     */
    protected static function runCommand($command): void
    {
        $command = sprintf('%s --quiet --no-interaction --env=test', $command);
        $kernel = new Kernel('test', true, false);
        $kernel->boot();
        $application = new RoadizApplication($kernel);
        $application->setAutoExit(false);
        $application->setCatchExceptions(false);
        $application->run(new StringInput($command));
    }
}
