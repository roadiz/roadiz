<?php
declare(strict_types=1);

use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Tests\SerializedEntityTestTrait;

class NodeTypeTest extends \PHPUnit\Framework\TestCase
{
    use SerializedEntityTestTrait;

    /**
     * @dataProvider sourceEntityClassNameProvider
     * @param $sourceName
     * @param $expected
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
     * @param $sourceName
     * @param $expected
     */
    public function testSourceEntityTableName($sourceName, $expected)
    {
        // Arrange
        $a = new NodeType();
        $a->setName($sourceName);
        // Assert
        $this->assertEquals($expected, $a->getSourceEntityTableName());
    }

    /*
     * Test empty object serialization
     */
    public function testSerialize()
    {
        // Arrange
        $a = new NodeType();
        // Assert
        $this->assertJson($this->getSerializer()->serialize($a, 'json'));
    }

    /**
     * @dataProvider setNameProvider
     * @param $sourceName
     * @param $expected
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
        return [
            ["Ligula  $* _--Egestas Mattis Nullam", "LigulaEgestasMattisNullam"],
            ["Véèsti buœlum_Rïsus", "VeestiBuoelumRisus"],
            ["J'aime les sushis", "JAimeLesSushis"],
        ];
    }
    public function sourceEntityClassNameProvider()
    {
        return [
            ["Ligula  $* _--Egestas Mattis Nullam", "NSLigulaEgestasMattisNullam"],
            ["Véèsti buœlum Rïsus", "NSVeestiBuoelumRisus"],
            ["J'aime les sushis", "NSJAimeLesSushis"],
        ];
    }
    public function sourceEntityTableNameProvider()
    {
        return [
            ["Ligula  $* _--Egestas Mattis Nullam", "ns_ligulaegestasmattisnullam"],
            ["Véèsti buœlum Rïsus", "ns_veestibuoelumrisus"],
            ["J'aime les sushis", "ns_jaimelessushis"],
        ];
    }
}
