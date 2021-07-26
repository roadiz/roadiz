<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Bags;

use Doctrine\DBAL\DBALException;
use Doctrine\Persistence\ManagerRegistry;
use RZ\Roadiz\Bag\LazyParameterBag;
use RZ\Roadiz\Core\Entities\Role;
use RZ\Roadiz\Core\Repositories\RoleRepository;

class Roles extends LazyParameterBag
{
    private ManagerRegistry $managerRegistry;
    private ?RoleRepository $repository = null;

    /**
     * @param ManagerRegistry $managerRegistry;
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct();
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @return RoleRepository
     */
    public function getRepository()
    {
        if (null === $this->repository) {
            $this->repository = $this->managerRegistry->getRepository(Role::class);
        }
        return $this->repository;
    }

    protected function populateParameters(): void
    {
        try {
            $roles = $this->getRepository()->findAll();
            $this->parameters = [];
            /** @var Role $role */
            foreach ($roles as $role) {
                $this->parameters[$role->getRole()] = $role;
            }
        } catch (DBALException $e) {
            $this->parameters = [];
        }
        $this->ready = true;
    }

    /**
     * Get role by name or create it if non-existent.
     *
     * @param string $key
     * @param null   $default
     *
     * @return Role
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function get($key, $default = null): Role
    {
        $role = parent::get($key, $default);

        if (null === $role) {
            $role = new Role($key);
            $this->managerRegistry->getManagerForClass(Role::class)->persist($role);
            $this->managerRegistry->getManagerForClass(Role::class)->flush();
        }

        return $role;
    }
}
