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
 * @file RolesBag.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Bags;

use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\core\Entities\Role;

/**
 * Roles bag used to get quickly a role entity
 * and to create it automatically if it does not exist.
 */
class RolesBag
{
    /**
     * Cached roles values.
     *
     * @var array
     */
    protected static $roles = [];

    /**
     * Get role by name or create it if non-existant.
     *
     * @param string $roleName
     *
     * @return RZ\Roadiz\core\Entities\Role
     */
    public static function get($roleName)
    {
        if (!isset(static::$roles[$roleName])) {
            static::$roles[$roleName] =
                    Kernel::getService('em')
                    ->getRepository('RZ\Roadiz\Core\Entities\Role')
                    ->findOneBy(['name'=>$roleName]);

            if (null === static::$roles[$roleName]) {
                static::$roles[$roleName] = new Role();

                static::$roles[$roleName]->setName($roleName);
                Kernel::getService('em')->persist(static::$roles[$roleName]);
                Kernel::getService('em')->flush();
            }
        }

        return static::$roles[$roleName];
    }
}
