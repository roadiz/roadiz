<?php

use RZ\Renzo\Core\Handlers\NodeHandler;
use RZ\Renzo\Core\Entities\NodesSources;
use RZ\Renzo\Core\Entities\Node;
use RZ\Renzo\Core\Entities\Translation;
use GeneratedNodeSources\NSPage;
use RZ\Renzo\CMS\Controllers\ImportController;
use RZ\Renzo\Core\Kernel;

class NodeHandlerTest extends PHPUnit_Framework_TestCase
{
    private static $entityCollection;
    private static $runtimeCollection;

    /**
     * @dataProvider duplicateProvider
     */
    public function testDuplicate($node, $key)
    {

        $nbNode = count(static::$runtimeCollection);

        $duplicatedNode = $node->getHandler()->duplicate();
        $node = Kernel::getService("em")->find("RZ\Renzo\Core\Entities\Node", $node->getId());
        $this->assertEquals($node->getNodeSources()->count(), $duplicatedNode->getNodeSources()->count());
        $this->assertEquals($node->getChildren()->count(), $duplicatedNode->getChildren()->count());
        $this->assertEquals($node->getStackTypes()->count(), $duplicatedNode->getStackTypes()->count());
        $this->assertEquals($node->getTags()->count(), $duplicatedNode->getTags()->count());
        $this->assertEquals($node->getParent(), $duplicatedNode->getParent());

        static::$runtimeCollection[] = $duplicatedNode;

        $this->assertEquals($nbNode + 1, count(static::$runtimeCollection));
        // Assert
        //$this->assertEquals($expected, $value);
    }

    public static function duplicateProvider()
    {
        static::$entityCollection = array();
        static::$runtimeCollection = array();

        date_default_timezone_set('Europe/Paris');
        ImportController::importContent(RENZO_ROOT . '/tests/Fixtures/Handlers/Page.json', "RZ\Renzo\CMS\Importers\NodeTypesImporter", null);

        $nodeType = Kernel::getService("em")
                        ->getRepository('RZ\Renzo\Core\Entities\NodeType')
                        ->findOneByName('Page');
        $node = new Node($nodeType);
        $node->setNodeName("testNode" . uniqid());
        $node->setPublished(true);
        Kernel::getService("em")->persist($node);
        $tran = Kernel::getService("em")
                    ->getRepository('RZ\Renzo\Core\Entities\Translation')
                    ->findDefault();
        $src = new NSPage($node, $tran);
        $src->setTitle("testNode");
        $src->setContent("This is TESTNODE!");
        Kernel::getService("em")->persist($src);

        Kernel::getService("em")->flush();
        static::$entityCollection[] = $node;

        return array(array(static::$entityCollection[0], 0));
    }

    /**
     * Remove test entities.
     */
    public static function tearDownAfterClass()
    {
        foreach (static::$entityCollection as $node) {
            $node = Kernel::getService("em")->find("RZ\Renzo\Core\Entities\Node", $node->getId());
            Kernel::getService('em')->refresh($node);
            Kernel::getService('em')->remove($node);
        }

        foreach (static::$runtimeCollection as $node) {
            $node = Kernel::getService("em")->find("RZ\Renzo\Core\Entities\Node", $node->getId());
            Kernel::getService('em')->refresh($node);
            Kernel::getService('em')->remove($node);
        }

        Kernel::getService('em')->flush();
        Kernel::getService('em')->clear(); // Detaches all objects from Doctrine!
    }
}

