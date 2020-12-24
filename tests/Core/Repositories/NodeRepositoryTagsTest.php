<?php

use Doctrine\ORM\Tools\ToolsException;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Tests\DefaultThemeWithNodesDependentCase;

class NodeRepositoryTagsTest extends DefaultThemeWithNodesDependentCase
{
    /**
     * @dataProvider getByTagInclusiveProvider
     * @param $tagsNames
     * @param $expectedNodeCount
     */
    public function testGetByTagInclusive($tagsNames, $expectedNodeCount)
    {
        $tags = static::getManager()
            ->getRepository(Tag::class)
            ->findByTagName($tagsNames);

        $nodeCount = static::getManager()
            ->getRepository(Node::class)
            ->setDisplayingNotPublishedNodes(true)
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
            [['unittest-tag-4'], 2],
            [['unittest-tag-1', 'unittest-tag-2'], 3],
            [['unittest-tag-1', 'unittest-tag-3'], 3],
            [['unittest-tag-2', 'unittest-tag-3'], 2],
            [['unittest-tag-1', 'unittest-tag-4'], 3],
        ];
    }

    /**
     * @dataProvider getByTagExclusiveProvider
     * @param $tagsNames
     * @param $expectedNodeCount
     */
    public function testGetByTagExclusive($tagsNames, $expectedNodeCount)
    {
        $tags = static::getManager()
            ->getRepository(Tag::class)
            ->findByTagName($tagsNames);

        $nodeCount = static::getManager()
            ->getRepository(Node::class)
            ->setDisplayingNotPublishedNodes(true)
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
    /**
     * @throws ToolsException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $translation = static::getManager()
            ->getRepository(Translation::class)
            ->findDefault();

        /*
         * Make this test available only if Page node-type exists.
         */
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
            static::createTag($value, $translation);
        }
        static::getManager()->flush();

        /*
         * Adding nodes
         */
        foreach ($nodes as $value) {
            $node = static::createPageNode($value[0], $translation);

            /*
             * Adding tags
             */
            foreach ($value[1] as $tagName) {
                $tag = static::getManager()
                    ->getRepository(Tag::class)
                    ->findOneByTagName($tagName);
                if (null !== $tag) {
                    $node->addTag($tag);
                } else {
                    throw new \RuntimeException('Cannot find tag');
                }
            }
        }
        static::getManager()->flush();
    }
}
