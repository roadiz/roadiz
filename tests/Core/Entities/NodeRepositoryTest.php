<?php

use Doctrine\Common\Collections\ArrayCollection;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Entities\TagTranslation;
use RZ\Roadiz\Core\Kernel;

class NodeRepositoryTest extends PHPUnit_Framework_TestCase
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
            $sourceClass = NodeType::getGeneratedEntitiesNamespace().'\\'.$type->getSourceEntityClassName();

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
                    $node = new Node();
                    $node->setNodeName($value[0]);
                    $node->setNodeType($type);
                    Kernel::getService('em')->persist($node);

                    $ns = new $sourceClass($node, $translation);
                    $ns->setTitle($value[0]);

                    static::$nodeCollection->add($node);

                    Kernel::getService('em')->persist($ns);
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
    }
}
