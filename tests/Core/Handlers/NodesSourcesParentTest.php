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
 * @file NodesSourcesParentTest.php
 * @author Ambroise Maupate
 */

use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Tests\SchemaDependentCase;

/**
 * Class NodesSourcesHandlerTest
 */
class NodesSourcesParentTest extends SchemaDependentCase
{
    public function tearDown(): void
    {
        $nodes = static::getManager()
            ->getRepository(Node::class)
            ->setDisplayingNotPublishedNodes(true)
            ->findAll();

        foreach ($nodes as $node) {
            static::getManager()->remove($node);
        }
        static::getManager()->flush();
    }

    /**
     * Nothing special to do except init collection
     * array.
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $fr = new Translation();
        $fr->setName('fr');
        $fr->setLocale('fr');
        $fr->setDefaultTranslation(true);
        $fr->setAvailable(true);

        $en = new Translation();
        $en->setName('en');
        $en->setLocale('en');
        $en->setDefaultTranslation(false);
        $en->setAvailable(true);

        static::getManager()->persist($fr);
        static::getManager()->persist($en);
        static::getManager()->flush();
    }

    public function testGetParent()
    {
        $sources = $this->getSourcesParentsProvider();

        foreach ($sources as $source) {
            /** @var NodesSources|null $nodeSource */
            $nodeSource = $source[0];
            /** @var NodesSources|null $expectedParent */
            $expectedParent = $source[1];

            if (null === $expectedParent) {
                $this->assertNull($nodeSource->getParent());
            } else {
                $this->assertEquals($nodeSource->getParent()->getId(), $expectedParent->getId());
            }
        }
    }

    /**
     * @return array
     */
    private function getSourcesParentsProvider()
    {
        $sources = [];

        $fr = static::getManager()
            ->getRepository(Translation::class)
            ->findOneByLocale('fr');

        $n1 = static::createNode(uniqid(), $fr);
        $ns1 = $n1->getNodeSources()->first();

        $n2 = static::createNode(uniqid(), $fr);
        $n2->setParent($n1);
        $ns2 = $n2->getNodeSources()->first();

        $n3 = static::createNode(uniqid(), $fr);
        $n3->setParent($n1);
        $ns3 = $n3->getNodeSources()->first();

        $n4 = static::createNode(uniqid(), $fr);
        $n4->setParent($n3);
        $ns4 = $n4->getNodeSources()->first();

        $sources[] = [$ns1, null];
        $sources[] = [$ns2, $ns1];
        $sources[] = [$ns3, $ns1];
        $sources[] = [$ns4, $ns3];

        static::getManager()->flush();

        return $sources;
    }
}
