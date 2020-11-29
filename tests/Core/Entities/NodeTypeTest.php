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
 * @file NodeTypeTest.php
 * @author Ambroise Maupate
 */
use RZ\Roadiz\Core\Entities\NodeType;

class NodeTypeTest extends \PHPUnit\Framework\TestCase
{
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
