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
use Doctrine\ORM\EntityNotFoundException;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Tests\DefaultThemeDependentCase;
use RZ\Roadiz\Utils\Node\NodeDuplicator;

class NodeHandlerTest extends DefaultThemeDependentCase
{
    private static $runtimeCollection;

    public function testDuplicate()
    {
        $node = null;
        /** @var Translation $translation */
        $translation = static::getManager()
            ->getRepository(Translation::class)
            ->findDefault();

        if (null !== $translation) {
            try {
                $node = static::createPageNode("testDuplicate Original node", $translation);
                static::getManager()->flush();
                $nbNode = count(static::$runtimeCollection);

                $duplicator = new NodeDuplicator($node, static::getManager());
                $duplicatedNode = $duplicator->duplicate();

                $this->assertEquals($node->getNodeSources()->count(), $duplicatedNode->getNodeSources()->count());
                $this->assertEquals($node->getChildren()->count(), $duplicatedNode->getChildren()->count());
                $this->assertEquals($node->getStackTypes()->count(), $duplicatedNode->getStackTypes()->count());
                $this->assertEquals($node->getTags()->count(), $duplicatedNode->getTags()->count());
                $this->assertEquals($node->getParent(), $duplicatedNode->getParent());

                static::$runtimeCollection->add($duplicatedNode);

                $duplicatedNode->getNodeSources()->first()->setTitle("testNodeDuplicated");

                $this->assertEquals($nbNode + 1, count(static::$runtimeCollection));

            } catch (EntityNotFoundException $e) {
                $this->markTestIncomplete($e->getMessage());
            }
        } else {
            $this->markTestIncomplete('Default translation does not exist.');
        }
    }

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        static::$runtimeCollection = new ArrayCollection();
    }
}
