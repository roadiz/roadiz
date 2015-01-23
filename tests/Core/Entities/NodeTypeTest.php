<?php

use RZ\Roadiz\Core\Entities\NodeType;

class NodeTypeTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider sourceEntityClassNameProvider
     */
    public function testSourceEntityClassName($sourceName, $expected)
    {
        // Arrange
        $a = new NodeType();
        $a->setName($sourceName);
        // Assert
        $this->assertEquals($expected, $a->getSourceEntityClassName());
    }

    /**
     * @dataProvider sourceEntityTableNameProvider
     */
    public function testSourceEntityTableName($sourceName, $expected)
    {
        // Arrange
        $a = new NodeType();
        $a->setName($sourceName);
        // Assert
        $this->assertEquals($expected, $a->getSourceEntityTableName());
    }

    /**
     * @dataProvider setNameProvider
     */
    public function testSetName($sourceName, $expected)
    {
        // Arrange
        $a = new NodeType();
        $a->setName($sourceName);

        // Assert
        $this->assertEquals($expected, $a->getName());
    }

    public function setNameProvider()
    {
        return array(
            array("Ligula  $* _--Egestas Mattis Nullam", "LigulaEgestasMattisNullam"),
            array("Véèsti buœlum_Rïsus", "VeestiBuoelumRisus"),
            array("J'aime les sushis", "JAimeLesSushis"),
        );
    }
    public function sourceEntityClassNameProvider()
    {
        return array(
            array("Ligula  $* _--Egestas Mattis Nullam", "NSLigulaEgestasMattisNullam"),
            array("Véèsti buœlum Rïsus", "NSVeestiBuoelumRisus"),
            array("J'aime les sushis", "NSJAimeLesSushis"),
        );
    }
    public function sourceEntityTableNameProvider()
    {
        return array(
            array("Ligula  $* _--Egestas Mattis Nullam", "ns_ligulaegestasmattisnullam"),
            array("Véèsti buœlum Rïsus", "ns_veestibuoelumrisus"),
            array("J'aime les sushis", "ns_jaimelessushis"),
        );
    }
}
