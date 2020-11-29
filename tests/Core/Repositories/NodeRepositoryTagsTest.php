<?php
/**
 * Copyright (c) 2018. Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
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
 * @file NodeRepositoryTagsTest.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\Tools\ToolsException;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Tests\DefaultThemeDependentCase;

class NodeRepositoryTagsTest extends DefaultThemeDependentCase
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
