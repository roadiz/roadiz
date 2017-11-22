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
use Doctrine\ORM\Events;
use Doctrine\ORM\Tools\SchemaTool;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Events\DataInheritanceEvent;
use RZ\Roadiz\Utils\Clearer\AssetsClearer;
use RZ\Roadiz\Utils\Clearer\ConfigurationCacheClearer;
use RZ\Roadiz\Utils\Clearer\OPCacheClearer;
use RZ\Roadiz\Utils\Clearer\RoutingCacheClearer;
use RZ\Roadiz\Utils\Clearer\TemplatesCacheClearer;
use RZ\Roadiz\Utils\Clearer\TranslationsCacheClearer;


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
     * @var EntityManager
     */
    static $entityManager;

    /**
     * @throws \Doctrine\ORM\Tools\ToolsException
     */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        $em = static::getManager();
        $schemaTool = new SchemaTool($em);
        $metadata = $em->getMetadataFactory()->getAllMetadata();

        // Drop and recreate tables for all entities
        $dropSQL = $schemaTool->getDropDatabaseSQL();
        if (count($dropSQL) > 0) {
            throw new \PHPUnit_Framework_RiskyTestError('Test database is not empty! Do not execute tests on a running Roadiz db.');
        }
        $schemaTool->dropDatabase();
        $schemaTool->createSchema($metadata);
    }

    /**
     * @inheritDoc
     */
    protected function tearDown()
    {
        parent::tearDown();

        /*
         * Empty caches
         */
        $kernel = new \RZ\Roadiz\Core\Kernel('test', true);
        $clearers = [
            // PROD
            new AssetsClearer($kernel->getCacheDir()),
            new RoutingCacheClearer($kernel->getCacheDir()),
            new TemplatesCacheClearer($kernel->getCacheDir()),
            new TranslationsCacheClearer($kernel->getCacheDir()),
            new ConfigurationCacheClearer($kernel->getCacheDir()),
            new OPCacheClearer(),
        ];
        foreach ($clearers as $clearer) {
            $clearer->clear();
        }
        $kernel->shutdown();
    }

    public static function tearDownAfterClass()
    {
        $em = static::getManager();
        $schemaTool = new SchemaTool($em);

        // Drop and recreate tables for all entities
        $schemaTool->dropDatabase();
        $em->close();
        static::$entityManager = null;

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
        if (static::$entityManager === null) {
            $config = static::$kernel->get('config');
            $emConfig = static::$kernel->get('em.config');
            static::$entityManager = EntityManager::create($config["doctrine"], $emConfig);
            $evm = static::$entityManager->getEventManager();

            /*
             * Create dynamic discriminator map for our Node system
             */
            $evm->addEventListener(
                Events::loadClassMetadata,
                new DataInheritanceEvent(static::$kernel->getContainer())
            );

            /*
             * Inject doctrine event subscribers for
             * a service to be able to add new ones from themes.
             */
            foreach (static::$kernel->get('em.eventSubscribers') as $eventSubscriber) {
                $evm->addEventSubscriber($eventSubscriber);
            }
        }

        return static::$entityManager;
    }
}
