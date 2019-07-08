<?php
/**
 * Copyright (c) 2018. Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
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
 * @file RoleJsonSerializerTest.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

use RZ\Roadiz\Core\Entities\Role;
use RZ\Roadiz\Core\Serializers\RoleJsonSerializer;
use RZ\Roadiz\Tests\SchemaDependentCase;

/**
 * @deprecated
 */
class RoleJsonSerializerTest extends SchemaDependentCase
{

    /**
     * @dataProvider deserializeProvider
     * @param $json
     * @throws Exception
     */
    public function testDeserialize($json)
    {
        $serializer = new RoleJsonSerializer();
        $role = $serializer->deserialize($json);

        $this->assertEquals(Role::class, get_class($role));
        $this->assertNotNull($role->getRole());

        $this->get('em')->persist($role);
        $this->get('em')->flush();

        // Assert
        $this->assertNotNull($role->getId());
    }

    /**
     * Provider for testDeserialize.
     *
     * Needs:
     *
     * * A valid Json file => the imported settings **count**
     *
     */
    public static function deserializeProvider()
    {
        return [
            [
                '{"name": "ROLE_TEST"}',
            ],
        ];
    }

    /**
     * @dataProvider deserializeReturnTypeProvider
     *
     * @param $json
     * @param $expected
     * @param $expectedRole
     */
    public function testDeserializeReturnType($json, $expected, $expectedRole)
    {
        $serializer = new RoleJsonSerializer();
        $output = $serializer->deserialize($json);

        // Assert
        $this->assertEquals($expected, get_class($output));
        $this->assertEquals($expectedRole, $output->getRole());
    }
    /**
     * Provider for testDeserializeReturnType.
     *
     * Needs:
     *
     * * A valid Json file => return value Type
     *
     */
    public static function deserializeReturnTypeProvider()
    {
        return [
            [
                '{"name": "ROLE_TEST"}',
                Role::class,
                'ROLE_TEST'
            ],
        ];
    }
}
