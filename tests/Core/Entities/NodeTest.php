<?php

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

        try {
            $root = static::createPageNode('root node', $translation);
            static::getManager()->flush();
            $node1 = static::createPageNode('node 1', $translation, $root);
            $node2 = static::createPageNode('node 2', $translation, $root);
            $node3 = static::createPageNode('node 3', $translation, $root);
            $node4 = static::createPageNode('node 4', $translation, $root);
            static::getManager()->flush();

            $this->assertEquals(4, $root->getChildren()->count());
            $this->assertEquals(1, $root->getPosition());
            $this->assertEquals(1, $node1->getPosition());
            $this->assertEquals(2, $node2->getPosition());
            $this->assertEquals(3, $node3->getPosition());
            $this->assertEquals(4, $node4->getPosition());
        } catch (EntityNotFoundException $e) {
            $this->markTestIncomplete($e->getMessage());
        }
    }
}
