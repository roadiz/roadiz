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
 * @file RoleTest.php
 * @author Thomas Aufresne
 */

use RZ\Roadiz\Core\Entities\Role;
use Symfony\Component\String\UnicodeString;

/**
 * Test Role features
 */
class RoleTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider snakeProvider
     * @param string $input
     * @param string $expected
     */
    public function testSnake($input, $expected)
    {
        $output = (new UnicodeString($input))
            ->ascii()
            ->folded()
            ->snake()
            ->upper()
            ->toString()
        ;
        $this->assertEquals($expected, $output);
    }

    public function snakeProvider()
    {
        return [
            ["ROLE_TEST", "ROLE_TEST"],
            ["ROLE_TEST_TEST", "ROLE_TEST_TEST"],
            ["ROLE_ASDF_AASDF", "ROLE_ASDF_AASDF"],
        ];
    }

    /**
     * @dataProvider roleNameProvider
     * @param $roleName
     * @param $expected
     */
    public function testRoleName($roleName, $expected)
    {
        $a = new Role($roleName);
        $this->assertEquals($expected, $a->getRole());
    }

    public function roleNameProvider()
    {
        return [
            ["role___àsdfasdf", "ROLE_ASDFASDF"],
            ["asdf ààsdf", "ROLE_ASDF_AASDF"],
            ["asdfasdf", "ROLE_ASDFASDF"],
            ["role___test", "ROLE_TEST"],
            ["role___test", "ROLE_TEST"],
            ["ROLE_TEST", "ROLE_TEST"],
            ["tèst tèst", "ROLE_TEST_TEST"],
            ["ROLE_TEST_TEST", "ROLE_TEST_TEST"],
            ["role_test_test", "ROLE_TEST_TEST"],
            ["ROLE_ASDF_AASDF", "ROLE_ASDF_AASDF"],
            ["role_asdf_aasdf", "ROLE_ASDF_AASDF"],
        ];
    }
}
