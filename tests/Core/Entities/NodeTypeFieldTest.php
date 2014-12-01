<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file NodeTypeFieldTest.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
use RZ\Roadiz\Core\Entities\NodeTypeField;
/**
 * Test node-type field features
 */
class NodeTypeFieldTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getGetterNameProvider
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
     */
    public function testSetName($sourceName, $expected)
    {
        // Arrange
        $a = new NodeTypeField();
        $a->setName($sourceName);

        // Assert
        $this->assertEquals($expected, $a->getName());
    }

    public function setNameProvider()
    {
        return array(
            array("Ligula  $* _--Egestas Mattis Nullam", "ligula_egestas_mattis_nullam"),
            array("Véèsti buœlum_Rïsus", "veesti_buoelum_risus"),
            array("J'aime les sushis", "j_aime_les_sushis"),
        );
    }
    public function getGetterNameProvider()
    {
        return array(
            array("Ligula  $* _--Egestas Mattis Nullam", "getLigulaegestasmattisnullam"),
            array("Véèsti buœlum Rïsus", "getVeestibuoelumrisus"),
            array("J'aime les sushis", "getJaimelessushis"),
        );
    }
    public function getSetterNameProvider()
    {
        return array(
             array("Ligula  $* _--Egestas Mattis Nullam", "setLigulaegestasmattisnullam"),
            array("Véèsti buœlum Rïsus", "setVeestibuoelumrisus"),
            array("J'aime les sushis", "setJaimelessushis"),
        );
    }
}
