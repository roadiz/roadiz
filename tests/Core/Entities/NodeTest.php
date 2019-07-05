<?php
/**
 * Copyright © 2014, Ambroise Maupate and Julien Blanchet
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
 *
 * @file NodeTest.php
 * @author Ambroise Maupate
 */
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityNotFoundException;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Tests\DefaultThemeDependentCase;

/**
 * Test Node features
 */
class NodeTest extends DefaultThemeDependentCase
{
    /**
     * @dataProvider nodeNameProvider
     * @param $nodeName
     * @param $expected
     */
    public function testNodeName($nodeName, $expected)
    {
        // Arrange
        $a = new Node();

        // Act
        $a->setNodeName($nodeName);

        // Assert
        $this->assertEquals($expected, $a->getNodeName());
    }

    public function nodeNameProvider()
    {
        return [
            ["Ligula  $* _--Egestas Mattis Nullam", "ligula-egestas-mattis-nullam"],
            ["Véèsti_buœlum Rïsus", "veesti-buoelum-risus"],
            ["J'aime les sushis", "j-aime-les-sushis"],
            ["J’aime les sushis", "j-aime-les-sushis"],
            ["Éditeur", "editeur"],
            ["À propos", "a-propos"],
            ["Ébène", "ebene"],
        ];
    }

    public function testNodePositions()
    {
        $translation = static::getManager()
            ->getRepository(Translation::class)
            ->findDefault();

        $collection = new ArrayCollection();

        try {
            $root = static::createPageNode('root node', $translation);
            static::getManager()->flush();

            $node1 = static::createPageNode('node 1', $translation, $root);
            $collection->add($node1);
            static::getManager()->flush();

            $node2 = static::createPageNode('node 2', $translation, $root);
            $collection->add($node2);
            static::getManager()->flush();

            $node3 = static::createPageNode('node 3', $translation, $root);
            $collection->add($node3);
            static::getManager()->flush();

            $node4 = static::createPageNode('node 4', $translation, $root);
            $collection->add($node4);
            static::getManager()->flush();

            $this->assertEquals(4, $root->getChildren()->count());
            $this->assertEquals(1, $node1->getPosition());
            $this->assertEquals(2, $node2->getPosition());
            $this->assertEquals(3, $node3->getPosition());
            $this->assertEquals(4, $node4->getPosition());

            foreach ($collection as $node) {
                static::getManager()->remove($node);
            }
            static::getManager()->flush();
        } catch (EntityNotFoundException $e) {
            $this->markTestIncomplete($e->getMessage());
        }
    }
}
