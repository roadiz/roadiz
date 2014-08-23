<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file NodeTest.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
use RZ\Renzo\Core\Entities\Node;
/**
 * Test Node features
 */
class NodeTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider nodeNameProvider
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
        return array(
            array("Ligula  $* _--Egestas Mattis Nullam", "ligula-egestas-mattis-nullam"),
            array("Véèsti_buœlum Rïsus", "veesti-buoelum-risus"),
            array("J'aime les sushis", "j-aime-les-sushis"),
        );
    }
}
