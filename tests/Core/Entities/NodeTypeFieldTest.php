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
 * @file NodeTypeFieldTest.php
 * @author Ambroise Maupate
 */
use RZ\Roadiz\Core\Entities\NodeTypeField;

/**
 * Test node-type field features
 */
class NodeTypeFieldTest extends \PHPUnit\Framework\TestCase
{
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
            array("Ligula  $* _--Egestas Mattis Nullam", "getLigulaEgestasMattisNullam"),
            array("Véèsti buœlum Rïsus", "getVeestiBuoelumRisus"),
            array("J'aime les sushis", "getJAimeLesSushis"),
        );
    }
    public function getSetterNameProvider()
    {
        return array(
             array("Ligula  $* _--Egestas Mattis Nullam", "setLigulaEgestasMattisNullam"),
            array("Véèsti buœlum Rïsus", "setVeestiBuoelumRisus"),
            array("J'aime les sushis", "setJAimeLesSushis"),
        );
    }
}
