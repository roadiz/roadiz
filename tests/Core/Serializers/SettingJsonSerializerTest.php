<?php

use RZ\Roadiz\Core\Entities\Setting;
use RZ\Roadiz\Core\Serializers\SettingJsonSerializer;
use RZ\Roadiz\Core\Kernel;
/**
 * Description.
 */
class SettingJsonSerializerTest extends PHPUnit_Framework_TestCase
{
    private static $entityCollection;

    /**
     * @dataProvider deserializeProvider
     */
    public function testDeserialize($json)
    {
        $setting = SettingJsonSerializer::deserialize($json);

        static::$entityCollection[] = $setting;
        Kernel::getService('em')->persist($setting);
        Kernel::getService('em')->flush();


        // Assert
        $this->assertNotNull($setting->getId());
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
                file_get_contents(ROADIZ_ROOT.'/tests/Fixtures/Serializers/settingJsonSerializer01.json'),
            ),
        );
    }


    /**
     * @dataProvider deserializeReturnTypeProvider
     */
    public function testDeserializeReturnType($json, $expected)
    {
        $output = SettingJsonSerializer::deserialize($json);

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
                file_get_contents(ROADIZ_ROOT.'/tests/Fixtures/Serializers/settingJsonSerializer01.json'),
                "RZ\Roadiz\Core\Entities\Setting"
            ),
        );
    }

    /**
     * Nothing special to do except init collection
     * array.
     */
    public static function setUpBeforeClass()
    {
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
        Kernel::getService('em')->clear(); // Detaches all objects from Doctrine!
    }
}
