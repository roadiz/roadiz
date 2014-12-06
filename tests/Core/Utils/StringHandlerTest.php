<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file StringHandlerTest.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */

use RZ\Roadiz\Core\Utils\StringHandler;

/**
 *
 */
class StringHandlerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider removeDiacriticsProvider
     */
    public function testRemoveDiacritics($input, $expected)
    {
        // Assert
        $this->assertEquals($expected, StringHandler::removeDiacritics($input));
    }

    public function removeDiacriticsProvider()
    {
        return array(
            array("à", "a"),
            array("é", "e"),
            array("À", "A"),
            array("É", "E"),
            array("œ", "oe"),
            array("ç", "c"),
            array("__à", "__a"),
            array("--é", "--e")
        );
    }

    /**
     * @dataProvider variablizeProvider
     */
    public function testVariablize($input, $expected)
    {
        // Assert
        $this->assertEquals($expected, StringHandler::variablize($input));
    }

    public function variablizeProvider()
    {
        return array(
            array("à", "a"),
            array("é", "e"),
            array("À", "a"),
            array("É", "e"),
            array("œ", "oe"),
            array("ç", "c"),
            array("__à", "_a"),
            array("--é", "_e")
        );
    }

    /**
     * @dataProvider camelCaseProvider
     */
    public function testCamelCase($input, $expected)
    {
        // Assert
        $this->assertEquals($expected, StringHandler::camelcase($input));
    }

    public function camelCaseProvider()
    {
        return array(
            array("Ligula  $* _--Egestas Mattis Nullam", "ligulaEgestasMattisNullam"),
            array("Véèsti buœlum Rïsus", "veestiBuoelumRisus"),
            array("J'aime les sushis", "jAimeLesSushis"),
            array("header_image", "headerImage"),
            array("JAime les_sushis", "jAimeLesSushis"),
        );
    }
}
