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
 * @file NodesSourcesRepositoryTagsTest.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Entities\Translation;

class NodesSourcesRepositoryTagsTest extends NodeRepositoryTagsTest
{
    /**
     * @dataProvider getByTagInclusiveProvider
     * @param $tagsNames
     * @param $expectedNodeCount
     */
    public function testGetByTagInclusive($tagsNames, $expectedNodeCount)
    {
        $translation = static::getManager()
            ->getRepository(Translation::class)
            ->findDefault();
        $tags = static::getManager()
            ->getRepository(Tag::class)
            ->findByTagName($tagsNames);

        $nodesSourcesCount = static::getManager()
            ->getRepository(NodesSources::class)
            ->setDisplayingNotPublishedNodes(true)
            ->countBy([
                'tags' => $tags,
                'translation' => $translation,
            ]);

        $this->assertEquals($expectedNodeCount, $nodesSourcesCount);
    }

    /**
     * @dataProvider getByTagExclusiveProvider
     * @param $tagsNames
     * @param $expectedNodeCount
     */
    public function testGetByTagExclusive($tagsNames, $expectedNodeCount)
    {
        $translation = static::getManager()
            ->getRepository(Translation::class)
            ->findDefault();
        $tags = static::getManager()
            ->getRepository(Tag::class)
            ->findByTagName($tagsNames);

        $nodesSourcesCount = static::getManager()
            ->getRepository(NodesSources::class)
            ->setDisplayingNotPublishedNodes(true)
            ->countBy([
                'tags' => $tags,
                'tagExclusive' => true,
                'translation' => $translation,
            ]);

        $this->assertEquals($expectedNodeCount, $nodesSourcesCount);
    }
}
