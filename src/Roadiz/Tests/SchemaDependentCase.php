<?php
/**
 * Copyright © 2016, Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file KernelDependentCase.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Tests;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
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
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        $em = static::getManager();
        $schemaTool = new SchemaTool($em);

        // Drop and recreate tables for all entities
        $dropSQL = $schemaTool->getDropDatabaseSQL();
        if (count($dropSQL) > 0) {
            throw new \PHPUnit_Framework_RiskyTestError('Test database is not empty! Do not execute tests on a running Roadiz db.');
        }

        static::runCommand('orm:schema-tool:create');
    }

    /**
     * @inheritDoc
     */
    protected function tearDown()
    {
        parent::tearDown();

        static::runCommand('cache:clear');
    }

    public static function tearDownAfterClass()
    {
        static::runCommand('orm:schema-tool:drop --force');

        parent::tearDownAfterClass();
    }

    /**
     * @param $title
     * @param Translation $translation
     * @return Node
     */
    protected static function createNode($title, Translation $translation)
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
     * @param $command
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
