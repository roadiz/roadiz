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
 * @file RoleRepositoryTest.php
 * @author Ambroise Maupate
 */
use Doctrine\Common\Collections\ArrayCollection;
use RZ\Roadiz\Core\Entities\RoleRepository;
use RZ\Roadiz\Core\Entities\Role;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Tests\KernelDependentCase;

class RoleRepositoryTest extends KernelDependentCase
{
    private static $entityCollection;

    /**
     * @dataProvider rolesProvider
     */
    public function testRoleValue($name, $expected)
    {
        $role = Kernel::getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\Role')
            ->findOneByName($name);

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
        parent::setUpBeforeClass();

        static::$entityCollection = new ArrayCollection();

        $roles = array(
            array("role___test", "ROLE_TEST"),
            array("role___test", "ROLE_TEST"),
            array("tèst tèst", "ROLE_TEST_TEST"),
        );

        foreach ($roles as $value) {
            $role = Kernel::getService('em')
                        ->getRepository('RZ\Roadiz\Core\Entities\Role')
                        ->findOneByName($value[1]);

            if (null === $role) {
                $a = new Role();
                $a->setName($value[0]);
                Kernel::getService('em')->persist($a);

                static::$entityCollection->add($role);
            }
        }

        Kernel::getService('em')->flush();
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
        parent::tearDownAfterClass();
    }
}
