<?php
/**
 * Copyright (c) 2017. Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
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
 * @file Roles.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace RZ\Roadiz\Core\Bags;

use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Core\Entities\Role;
use RZ\Roadiz\Core\Repositories\RoleRepository;

class Roles extends AbstractBag
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var RoleRepository
     */
    private $repository;

    /**
     * SettingsBag constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @return RoleRepository
     */
    public function getRepository()
    {
        if (null === $this->repository) {
            $this->repository = $this->entityManager->getRepository('RZ\Roadiz\Core\Entities\Role');
        }
        return $this->repository;
    }

    /**
     * Get role by name or create it if non-existant.
     *
     * @param string $roleName
     * @return Role
     */
    public function get($roleName)
    {
        $role = $this->getRepository()->findOneByName($roleName);

        if (null === $role) {
            $role = new Role();
            $role->setName($roleName);
            $this->entityManager->persist($role);
            $this->entityManager->flush($role);
        }

        return $role;
    }
}
