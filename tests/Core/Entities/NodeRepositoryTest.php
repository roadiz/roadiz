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
 * @file NodeRepositoryTest.php
 * @author Ambroise Maupate
 */
use Doctrine\Common\Collections\ArrayCollection;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Entities\TagTranslation;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Tests\KernelDependentCase;

class NodeRepositoryTest extends KernelDependentCase
{
    private static $nodeCollection;
    private static $tagCollection;

    /**
     * @dataProvider getByTagInclusiveProvider
     */
    public function testGetByTagInclusive($tagsNames, $expectedNodeCount)
    {
        $tags = Kernel::getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\Tag')
            ->findByTagName($tagsNames);

        $nodeCount = Kernel::getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\Node')
            ->countBy([
                'tags' => $tags,
            ]);

        $this->assertEquals($expectedNodeCount, $nodeCount);
    }

    public function getByTagInclusiveProvider()
    {
        return [
            [['unittest-tag-1'], 3],
            [['unittest-tag-2'], 1],
            [['unittest-tag-3'], 1],
            [['unittest-tag-1', 'unittest-tag-2'], 3],
            [['unittest-tag-1', 'unittest-tag-3'], 3],
            [['unittest-tag-2', 'unittest-tag-3'], 2],
            [['unittest-tag-1', 'unittest-tag-4'], 3],
        ];
    }

    /**
     * @dataProvider getByTagExclusiveProvider
     */
    public function testGetByTagExclusive($tagsNames, $expectedNodeCount)
    {
        $tags = Kernel::getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\Tag')
            ->findByTagName($tagsNames);

        $nodeCount = Kernel::getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\Node')
            ->countBy([
                'tags' => $tags,
                'tagExclusive' => true,
            ]);

        $this->assertEquals($expectedNodeCount, $nodeCount);
    }

    public function getByTagExclusiveProvider()
    {
        return [
            [['unittest-tag-1'], 3],
            [['unittest-tag-2'], 1],
            [['unittest-tag-3'], 1],
            [['unittest-tag-1', 'unittest-tag-2'], 1],
            [['unittest-tag-1', 'unittest-tag-3'], 1],
            [['unittest-tag-2', 'unittest-tag-3'], 0],
            [['unittest-tag-1', 'unittest-tag-4'], 2],
        ];
    }

    /*
     * ============================================================================
     * fixtures
     * ============================================================================
     */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        static::$nodeCollection = new ArrayCollection();
        static::$tagCollection = new ArrayCollection();

        $type = Kernel::getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\NodeType')
            ->findOneByName('Page');
        $translation = Kernel::getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\Translation')
            ->findDefault();

        /*
         * Make this test available only if Page node-type exists.
         */
        if (null !== $type) {
            $sourceClass = NodeType::getGeneratedEntitiesNamespace() . '\\' . $type->getSourceEntityClassName();

            $tags = [
                'unittest-tag-1',
                'unittest-tag-2',
                'unittest-tag-3',
                'unittest-tag-4',
            ];
            $nodes = [
                ["unittest-node1", ['unittest-tag-1', 'unittest-tag-4']],
                ["unittest-node2", ['unittest-tag-1', 'unittest-tag-2']],
                ["unittest-node3", ['unittest-tag-1', 'unittest-tag-3', 'unittest-tag-4']],
            ];

            /*
             * Adding Tags
             */
            foreach ($tags as $value) {
                $tag = Kernel::getService('em')
                    ->getRepository('RZ\Roadiz\Core\Entities\Tag')
                    ->findOneByTagName($value);

                if (null === $tag) {
                    $tag = new Tag();
                    $tag->setTagName($value);
                    Kernel::getService('em')->persist($tag);

                    $tt = new TagTranslation($tag, $translation);
                    $tt->setName($value);
                    Kernel::getService('em')->persist($tt);

                    static::$tagCollection->add($tag);
                }
            }
            Kernel::getService('em')->flush();

            /*
             * Adding nodes
             */
            foreach ($nodes as $value) {
                $node = Kernel::getService('em')
                    ->getRepository('RZ\Roadiz\Core\Entities\Node')
                    ->findOneByNodeName($value[0]);

                if (null === $node) {
                    $node = new Node($type);
                    $node->setNodeName($value[0]);
                    Kernel::getService('em')->persist($node);

                    $ns = new $sourceClass($node, $translation);
                    $ns->setTitle($value[0]);
                    Kernel::getService('em')->persist($ns);

                    static::$nodeCollection->add($node);
                }
                /*
                 * Adding tags
                 */
                foreach ($value[1] as $tagName) {
                    $tag = Kernel::getService('em')
                        ->getRepository('RZ\Roadiz\Core\Entities\Tag')
                        ->findOneByTagName($tagName);
                    if (null !== $tag) {
                        $node->addTag($tag);
                    }
                }
            }
            Kernel::getService('em')->flush();
        }
    }

    /**
     * Remove test entities.
     */
    public static function tearDownAfterClass()
    {
        foreach (static::$nodeCollection as $node) {
            foreach ($node->getNodeSources() as $ns) {
                Kernel::getService('em')->remove($ns);
            }
            Kernel::getService('em')->remove($node);
        }
        foreach (static::$tagCollection as $tag) {
            Kernel::getService('em')->remove($tag);
        }

        Kernel::getService('em')->flush();

        parent::tearDownAfterClass();
    }
}
