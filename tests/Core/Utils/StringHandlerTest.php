<?php
/*
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
 * @file StringHandlerTest.php
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

    /**
     * @dataProvider encodeWithSecretProvider
     */
    public function testEncodeWithSecret($input, $secret)
    {
        $code = StringHandler::encodeWithSecret($input, $secret);

        // Assert
        $this->assertEquals($input, StringHandler::decodeWithSecret($code, $secret));
    }

    public function encodeWithSecretProvider()
    {
        return array(
            array("Ligula  $* _--Egestas Mattis Nullam", "Commodo Pellentesque Sem Fusce Quam"),
            array("Véèsti buœlum Rïsus ", "  change#this#secret#very#important"),
            array("J'aime les sushis  ", " Fringilla Vulputate Dolor Inceptos"),
            array("auietauieauie@auietsrt.trr", "Sit Vestibulum Dolor Ullamcorper Aenean"),
            array("JAime les_sushis", "Sit Vestibulum Dolor"),
        );
    }

    /**
     * @dataProvider encodeWithSecretNoSaltProvider
     */
    public function testEncodeWithSecretNoSalt($input, $secret)
    {
        $this->setExpectedException('RZ\\Roadiz\\Core\\Exceptions\\EmptySaltException');

        $code = StringHandler::encodeWithSecret($input, $secret);

        // Assert
        $this->assertEquals($input, StringHandler::decodeWithSecret($code, $secret));
    }

    public function encodeWithSecretNoSaltProvider()
    {
        return array(
            array("Ligula  $* _--Egestas Mattis Nullam", ""),
            array("Véèsti buœlum Rïsus ", "  "),
            array("J'aime les sushis  ", "  "),
            array("auietauieauie@auietsrt.trr", PHP_EOL),
        );
    }
}
