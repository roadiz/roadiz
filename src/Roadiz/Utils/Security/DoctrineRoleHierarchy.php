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
 * @file DoctrineRoleHierarchy.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Utils\Security;

use Symfony\Component\Security\Core\Role\RoleHierarchy;
use Doctrine\ORM\EntityManager;


class DoctrineRoleHierarchy extends RoleHierarchy
{
    protected $em;

    /**
     * Constructor.
     *
     * @param array $hierarchy An array defining the hierarchy
     */
    public function __construct(EntityManager $em = null)
    {
        $this->em = $em;
    }

    /**
     * {@inheritdoc}
     */
    public function getReachableRoles(array $roles)
    {
        if (null === $this->map) {
            $this->buildRoleMap();
        }

        return parent::getReachableRoles($roles);
    }


    protected function buildRoleMap()
    {
        $this->map = [];

        if (null !== $this->em) {
            $hierarchy = [
                Role::ROLE_SUPERADMIN => $this->em->getRepository('RZ\Roadiz\Core\Entities\Role')->getAllBasicRoleName(),
            ];

            foreach ($hierarchy as $main => $roles) {
                $this->map[$main] = $roles;
                $visited = array();
                $additionalRoles = $roles;
                while ($role = array_shift($additionalRoles)) {
                    if (!isset($hierarchy[$role])) {
                        continue;
                    }
                    $visited[] = $role;
                    $this->map[$main] = array_unique(array_merge($this->map[$main], $hierarchy[$role]));
                    $additionalRoles = array_merge($additionalRoles, array_diff($hierarchy[$role], $visited));
                }
            }
        }
    }
}
