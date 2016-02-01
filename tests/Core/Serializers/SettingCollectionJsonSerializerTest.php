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
 * @file SettingCollectionJsonSerializerTest.php
 * @author Ambroise Maupate
 */
use RZ\Roadiz\Core\Entities\Setting;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\Serializers\SettingCollectionJsonSerializer;
use RZ\Roadiz\Tests\KernelDependentCase;

/**
 * Description.
 */
class SettingCollectionJsonSerializerTest extends KernelDependentCase
{
    private static $entityCollection;

    /**
     * @dataProvider deserializeProvider
     */
    public function testDeserialize($json, $expected)
    {
        $serializer = new SettingCollectionJsonSerializer();
        $settings = $serializer->deserialize($json);

        // Assert
        $this->assertEquals($settings->count(), $expected);
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
        return array(
            array(
                file_get_contents(ROADIZ_ROOT . '/tests/Fixtures/Serializers/settingCollectionJsonSerializer01.json'),
                3,
            ),
        );
    }

    /**
     * @dataProvider deserializeReturnTypeProvider
     */
    public function testDeserializeReturnType($json, $expected)
    {
        $serializer = new SettingCollectionJsonSerializer();
        $output = $serializer->deserialize($json);

        // Assert
        $this->assertEquals($expected, get_class($output));
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
        return array(
            array(
                file_get_contents(ROADIZ_ROOT . '/tests/Fixtures/Serializers/settingCollectionJsonSerializer01.json'),
                "Doctrine\Common\Collections\ArrayCollection",
            ),
        );
    }

    /**
     * Nothing special to do except init collection
     * array.
     */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        static::$entityCollection = array();
    }
    /**
     * Remove test entities.
     */
    public static function tearDownAfterClass()
    {
        foreach (static::$entityCollection as $setting) {
            Kernel::getService('em')->remove($setting);
        }

        Kernel::getService('em')->flush();

        parent::tearDownAfterClass();
    }
}
