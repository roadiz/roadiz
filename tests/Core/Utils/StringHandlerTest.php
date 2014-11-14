<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file StringHandlerTest.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */

use RZ\Renzo\Core\Utils\StringHandler;

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
     * @dataProvider removeVariablizeProvider
     */
    public function testVariablize($input, $expected)
    {
        // Assert
        $this->assertEquals($expected, StringHandler::variablize($input));
    }

    public function removeVariablizeProvider()
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
}
