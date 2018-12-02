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

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Core\Entities\Role;
use RZ\Roadiz\Core\Repositories\RoleRepository;
use Symfony\Component\HttpFoundation\ParameterBag;

class Roles extends ParameterBag
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
            $this->repository = $this->entityManager->getRepository(Role::class);
        }
        return $this->repository;
    }

    protected function populateParameters()
    {
        try {
            $roles = $this->getRepository()->findAll();
            $this->parameters = [];
            /** @var Role $role */
            foreach ($roles as $role) {
                $this->parameters[$role->getName()] = $role;
            }
        } catch (DBALException $e) {
            $this->parameters = [];
        }
    }

    /**
     * Get role by name or create it if non-existant.
     *
     * @param string $key
     * @param null $default
     * @return Role
     */
    public function get($key, $default = null): Role
    {
        if (!is_array($this->parameters)) {
            $this->populateParameters();
        }
        $role = parent::get($key, null);

        if (null === $role) {
            $role = new Role();
            $role->setName($key);
            $this->entityManager->persist($role);
            $this->entityManager->flush($role);
        }

        return $role;
    }

    /**
     * @return array
     */
    public function all(): array
    {
        if (!is_array($this->parameters)) {
            $this->populateParameters();
        }

        return parent::all();
    }
}
