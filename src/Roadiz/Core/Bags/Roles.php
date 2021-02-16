<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Bags;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\Core\Entities\Role;
use RZ\Roadiz\Core\Repositories\RoleRepository;

class Roles extends LazyParameterBag
{
    private EntityManagerInterface $entityManager;
    private ?RoleRepository $repository = null;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
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
            $this->entityManager->persist($role);
            $this->entityManager->flush();
        }

        return $role;
    }
}
