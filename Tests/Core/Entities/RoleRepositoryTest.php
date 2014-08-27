<?php

use RZ\Renzo\Core\Entities\RoleRepository;
use RZ\Renzo\Core\Entities\Role;
use RZ\Renzo\Core\Kernel;

class RoleRepositoryTest extends PHPUnit_Framework_TestCase
{
    //private static $entityCollection;

    /**
     * @dataProvider settingsProvider
     *//*
    public function testRoleValue($name, $expected)
    {
        $value = Kernel::getInstance()->em()
            ->getRepository('RZ\Renzo\Core\Entities\Role')
            ->findOneByName($name);

        // Assert
        $this->assertEquals($expected, $value);
    }

    public static function rolesProvider()
    {
        return array(
            array("ROLE_", "1"),
            array("test_de_setting_c", true),
            array("test_de_setting_c_c", "j-aime-les-sushis"),
            array("test_de_setting_c_c_c", "j-ai\"''me-les-sushis"),
            array("test_de_setting_c_c_c_c", "j-ai\"''me-les-suéàçshis"),
        );
    }

    public static function setUpBeforeClass()
    {
        static::$entityCollection = array();
        $settings = static::settingsProvider();

        foreach ($settings as $setting) {
            $s = new Setting();
            $s->setName($setting[0]);
            $s->setValue($setting[1]);
            Kernel::getInstance()->em()->persist($s);

            static::$entityCollection[] = $s;
        }

        Kernel::getInstance()->em()->flush();
    }*/
    /**
     * Remove test entities.
     *//*
    public static function tearDownAfterClass()
    {
        foreach (static::$entityCollection as $setting) {
            Kernel::getInstance()->em()->remove($setting);
        }

        Kernel::getInstance()->em()->flush();
        Kernel::getInstance()->em()->clear(); // Detaches all objects from Doctrine!
    }*/
}
