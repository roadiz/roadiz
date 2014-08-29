<?php

use RZ\Renzo\Core\Entities\RoleRepository;
use RZ\Renzo\Core\Entities\Role;
use RZ\Renzo\Core\Kernel;

class RoleRepositoryTest extends PHPUnit_Framework_TestCase
{
    private static $entityCollection;

    /**
     * @dataProvider rolesProvider
     */
    public function testRoleValue($name, $expected)
    {
        echo Kernel::getInstance()->em()
            ->getRepository('RZ\Renzo\Core\Entities\Role')
            ->countByName($name);

        $role = Kernel::getInstance()->em()
            ->getRepository('RZ\Renzo\Core\Entities\Role')
            ->findOneByName($name);

        static::$entityCollection[] = $role;

        // Assert
        $this->assertEquals($expected, $role->getName());
    }

    public function rolesProvider()
    {
        return array(
            array("role___test", "ROLE_TEST"),
            array("role___test", "ROLE_TEST"),
            array("tèst tèst", "ROLE_TEST_TEST"),
        );
    }

    public static function setUpBeforeClass()
    {
        static::$entityCollection = array();
    }

    /**
     * Remove test entities.
     */
    public static function tearDownAfterClass()
    {
        foreach (static::$entityCollection as $role) {
            Kernel::getInstance()->em()->remove($role);
        }

        Kernel::getInstance()->em()->flush();
        Kernel::getInstance()->em()->clear(); // Detaches all objects from Doctrine!
    }
}
