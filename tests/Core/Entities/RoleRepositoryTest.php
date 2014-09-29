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
        echo Kernel::getService('em')
            ->getRepository('RZ\Renzo\Core\Entities\Role')
            ->countByName($name);

        $role = Kernel::getService('em')
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
            Kernel::getService('em')->remove($role);
        }

        Kernel::getService('em')->flush();
        Kernel::getService('em')->clear(); // Detaches all objects from Doctrine!
    }
}
