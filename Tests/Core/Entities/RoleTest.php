<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file RoleTest.php
 * @copyright REZO ZERO 2014
 * @author Thomas Aufresne
 */

use RZ\Renzo\Core\Entities\Role;

/**
 * Test Role features
 */
class RoleTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider roleNameProvider
     */
    public function testRoleName($roleName, $expected)
    {
        // Arrange
        $a = new Role();

        // Act
        $a->setName($roleName);

        // Assert
        $this->assertEquals($expected, $a->getName());
    }

    public function roleNameProvider()
    {
        return array(
            array("role___asdfasdf", "ROLE_ASDFASDF"),
            array("asdf ààsdf", "ROLE_ASDF_AASDF"),
            array("asdfasdf", "ROLE_ASDFASDF"),
        );
    }
}
