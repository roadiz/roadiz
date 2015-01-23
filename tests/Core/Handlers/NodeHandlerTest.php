<?php

use RZ\Roadiz\Core\Handlers\NodeHandler;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\Translation;
use GeneratedNodeSources\NSPage;
use RZ\Roadiz\CMS\Controllers\ImportController;
use RZ\Roadiz\Core\Kernel;

class NodeHandlerTest extends PHPUnit_Framework_TestCase
{
    private static $entityCollection;
    private static $runtimeCollection;

    /**
     * @dataProvider duplicateProvider
     */
    public function testDuplicate($node)
    {
        $nbNode = count(static::$runtimeCollection);

        $duplicatedNode = $node->getHandler()->duplicate();

        $this->assertEquals($node->getNodeSources()->count(), $duplicatedNode->getNodeSources()->count());
        $this->assertEquals($node->getChildren()->count(), $duplicatedNode->getChildren()->count());
        $this->assertEquals($node->getStackTypes()->count(), $duplicatedNode->getStackTypes()->count());
        $this->assertEquals($node->getTags()->count(), $duplicatedNode->getTags()->count());
        $this->assertEquals($node->getParent(), $duplicatedNode->getParent());

        static::$entityCollection[] = $node;
        static::$runtimeCollection[] = $duplicatedNode;

        $duplicatedNode->getNodeSources()->first()->setTitle("testNodeDuplicated");

        $this->assertEquals($nbNode + 1, count(static::$runtimeCollection));

        Kernel::getService('em')->flush();
    }

    public static function duplicateProvider()
    {
        $nodeType = Kernel::getService("em")
                        ->getRepository('RZ\Roadiz\Core\Entities\NodeType')
                        ->findOneByName('Page');
        $tran = Kernel::getService("em")
                    ->getRepository('RZ\Roadiz\Core\Entities\Translation')
                    ->findDefault();

        if (null !== $nodeType &&
            null !== $tran) {
            $node = new Node($nodeType);
            $node->setNodeName("testNode" . uniqid());
            $node->setPublished(true);
            Kernel::getService("em")->persist($node);

            $src = new NSPage($node, $tran);
            $src->setTitle("testNode base");
            $src->setContent("This is TESTNODE!");

            Kernel::getService("em")->persist($src);
            Kernel::getService("em")->flush();

            return array(array($node));

        } else {
            return array();
        }
    }

    public static function setUpBeforeClass()
    {
        static::$entityCollection = array();
        static::$runtimeCollection = array();

        date_default_timezone_set('Europe/Paris');
        ImportController::importContent(
            ROADIZ_ROOT . '/tests/Fixtures/Handlers/Page.json',
            "RZ\Roadiz\CMS\Importers\NodeTypesImporter",
            null
        );
    }

    /**
     * Remove test entities.
     */
    public static function tearDownAfterClass()
    {
        foreach (static::$entityCollection as $node) {
            $node = Kernel::getService("em")->find("RZ\Roadiz\Core\Entities\Node", $node->getId());
            Kernel::getService('em')->remove($node);
        }

        foreach (static::$runtimeCollection as $node) {
            $node = Kernel::getService("em")->find("RZ\Roadiz\Core\Entities\Node", $node->getId());
            Kernel::getService('em')->remove($node);
        }

        Kernel::getService('em')->flush();
    }
}
