<?php

use RZ\Renzo\Core\Entities\Setting;
use RZ\Renzo\Core\Kernel;

class SessionTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider settingsProvider
     */
    public function testGetValue( $name, $expected )
    {
        $value = Kernel::getInstance()->em()
            ->getRepository('RZ\Renzo\Core\Entities\Setting')
            ->getValue($name);

        // Assert
        $this->assertEquals($expected, $value);
    }

    public static function settingsProvider()
    {
        return array(
            array("test_de_setting", "1"),
            array("test_de_setting_c", true),
            array("test_de_setting_c_c", "j-aime-les-sushis"),
            array("test_de_setting_c_c_c", "j-ai\"''me-les-sushis"),
            array("test_de_setting_c_c_c_c", "j-ai\"''me-les-suéàçshis"),
        );
    }

    public static function setUpBeforeClass()
    {
        $settings = static::settingsProvider();

        foreach ($settings as $setting) {
            $s = new Setting();
            $s->setName($setting[0]);
            $s->setValue($setting[1]);
            Kernel::getInstance()->em()->persist($s);
        }

        Kernel::getInstance()->em()->flush();
        Kernel::getInstance()->em()->clear(); // Detaches all objects from Doctrine!
    }

    public static function tearDownAfterClass()
    {
        $settings = static::settingsProvider();
        $settingsNames = array();

        foreach ($settings as $setting) {
            $settingsNames[] =$setting[0];
        }

        $objs = Kernel::getInstance()->em()
            ->getRepository('RZ\Renzo\Core\Entities\Setting')
            ->findBy(array('name'=>$settingsNames));

        foreach ($objs as $setting) {
            Kernel::getInstance()->em()->remove($setting);
        }

        Kernel::getInstance()->em()->flush();
        Kernel::getInstance()->em()->clear(); // Detaches all objects from Doctrine!
    }
}
