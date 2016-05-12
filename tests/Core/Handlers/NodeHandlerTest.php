<?php
/**
 * Copyright © 2015, Ambroise Maupate and Julien Blanchet
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
 * @file NodeHandlerTest.php
 * @author Ambroise Maupate
 */

use Doctrine\Common\Collections\ArrayCollection;
use GeneratedNodeSources\NSPage;
use RZ\Roadiz\CMS\Importers\NodeTypesImporter;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Handlers\NodeHandler;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Tests\KernelDependentCase;

class NodeHandlerTest extends KernelDependentCase
{
    private static $entityCollection;
    private static $runtimeCollection;

    public function testDuplicate()
    {
        $node = null;

        $nodeType = Kernel::getService("em")
            ->getRepository('RZ\Roadiz\Core\Entities\NodeType')
            ->findOneByName('Page');
        $tran = Kernel::getService("em")
            ->getRepository('RZ\Roadiz\Core\Entities\Translation')
            ->findDefault();

        if (null !== $nodeType &&
            null !== $tran) {
            $uniqId = uniqid();
            $node = new Node($nodeType);
            $node->setNodeName("unittest-NodeHandlerTest-" . $uniqId);
            $node->setPublished(true);
            Kernel::getService("em")->persist($node);

            $src = new NSPage($node, $tran);
            $src->setTitle("NodeHandlerTest-" . $uniqId);

            Kernel::getService("em")->persist($src);
            Kernel::getService("em")->flush();

            $nbNode = count(static::$runtimeCollection);

            $duplicator = new \RZ\Roadiz\Utils\Node\NodeDuplicator($node, Kernel::getService("em"));
            $duplicatedNode = $duplicator->duplicate();

            $this->assertEquals($node->getNodeSources()->count(), $duplicatedNode->getNodeSources()->count());
            $this->assertEquals($node->getChildren()->count(), $duplicatedNode->getChildren()->count());
            $this->assertEquals($node->getStackTypes()->count(), $duplicatedNode->getStackTypes()->count());
            $this->assertEquals($node->getTags()->count(), $duplicatedNode->getTags()->count());
            $this->assertEquals($node->getParent(), $duplicatedNode->getParent());

            static::$entityCollection->add($node);
            static::$runtimeCollection->add($duplicatedNode);

            $duplicatedNode->getNodeSources()->first()->setTitle("testNodeDuplicated");

            $this->assertEquals($nbNode + 1, count(static::$runtimeCollection));

            Kernel::getService('em')->flush();
        }
    }

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        static::$entityCollection = new ArrayCollection();
        static::$runtimeCollection = new ArrayCollection();

        $file = file_get_contents(ROADIZ_ROOT . '/tests/Fixtures/Handlers/Page.rzt');
        NodeTypesImporter::importJsonFile(
            $file,
            Kernel::getService('em')
        );
    }

    /**
     * Remove test entities.
     */
    public static function tearDownAfterClass()
    {
        foreach (static::$entityCollection as $node) {
            Kernel::getService('em')->remove($node);
        }

        foreach (static::$runtimeCollection as $node) {
            Kernel::getService('em')->remove($node);
        }

        Kernel::getService('em')->flush();

        parent::tearDownAfterClass();
    }
}
