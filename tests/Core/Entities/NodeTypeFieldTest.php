<?php
declare(strict_types=1);

use RZ\Roadiz\Core\Entities\NodeTypeField;
use RZ\Roadiz\Tests\SerializedEntityTestTrait;

/**
 * Test node-type field features
 */
class NodeTypeFieldTest extends \PHPUnit\Framework\TestCase
{
    use SerializedEntityTestTrait;
    /**
     * @dataProvider getGetterNameProvider
     * @param $sourceName
     * @param $expected
     */
    public function testGetGetterName($sourceName, $expected)
    {
        // Arrange
        $a = new NodeTypeField();
        $a->setName($sourceName);
        // Assert
        $this->assertEquals($expected, $a->getGetterName());
    }

    /**
     * @dataProvider getSetterNameProvider
     * @param $sourceName
     * @param $expected
     */
    public function testGetSetterName($sourceName, $expected)
    {
        // Arrange
        $a = new NodeTypeField();
        $a->setName($sourceName);
        // Assert
        $this->assertEquals($expected, $a->getSetterName());
    }

    /**
     * @dataProvider setNameProvider
     * @param $sourceName
     * @param $expected
     */
    public function testSetName($sourceName, $expected)
    {
        // Arrange
        $a = new NodeTypeField();
        $a->setName($sourceName);

        // Assert
        $this->assertEquals($expected, $a->getName());
    }

    /*
     * Test empty object serialization
     */
    public function testSerialize()
    {
        // Arrange
        $a = new NodeTypeField();
        // Assert
        $this->assertJson($this->getSerializer()->serialize($a, 'json'));
    }

    public function setNameProvider()
    {
        return [
            ["Ligula  $* _--Egestas Mattis Nullam", "ligula_egestas_mattis_nullam"],
            ["Véèsti buœlum_Rïsus", "veesti_buoelum_risus"],
            ["J'aime les sushis", "j_aime_les_sushis"],
            ["j_aime_les_sushis", "j_aime_les_sushis"],
        ];
    }
    public function getGetterNameProvider()
    {
        return [
            ["Ligula  $* _--Egestas Mattis Nullam", "getLigulaEgestasMattisNullam"],
            ["Véèsti buœlum Rïsus", "getVeestiBuoelumRisus"],
            ["J'aime les sushis", "getJAimeLesSushis"],
        ];
    }
    public function getSetterNameProvider()
    {
        return [
             ["Ligula  $* _--Egestas Mattis Nullam", "setLigulaEgestasMattisNullam"],
            ["Véèsti buœlum Rïsus", "setVeestiBuoelumRisus"],
            ["J'aime les sushis", "setJAimeLesSushis"],
        ];
    }
}
