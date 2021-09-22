<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Security;

use Doctrine\Persistence\ManagerRegistry;
use RZ\Roadiz\Core\Entities\Role;
use Symfony\Component\Security\Core\Role\RoleHierarchy;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

/**
 * @package RZ\Roadiz\Utils\Security
 */
final class DoctrineRoleHierarchy implements RoleHierarchyInterface
{
    private ?ManagerRegistry $managerRegistry;
    private ?RoleHierarchy $innerHierarchy = null;

    /**
     * @param ManagerRegistry|null $managerRegistry
     */
    public function __construct(?ManagerRegistry $managerRegistry = null)
    {
        $this->managerRegistry = $managerRegistry;

        if (null === $managerRegistry) {
            $this->innerHierarchy = new RoleHierarchy([
                Role::ROLE_SUPERADMIN => [Role::ROLE_BACKEND_USER, Role::ROLE_DEFAULT],
                Role::ROLE_BACKEND_USER => ['IS_AUTHENTICATED_ANONYMOUSLY'],
                Role::ROLE_DEFAULT => ['IS_AUTHENTICATED_ANONYMOUSLY'],
            ]);
        }
    }

    protected function getHierarchy(): RoleHierarchy
    {
        if (null === $this->innerHierarchy) {
            $roleRepository = $this->managerRegistry->getRepository(Role::class);
            $this->innerHierarchy = new RoleHierarchy([
                Role::ROLE_SUPERADMIN => $roleRepository->getAllBasicRoleName(),
                Role::ROLE_BACKEND_USER => ['IS_AUTHENTICATED_ANONYMOUSLY'],
                Role::ROLE_DEFAULT => ['IS_AUTHENTICATED_ANONYMOUSLY'],
            ]);
        }
        return $this->innerHierarchy;
    }

    public function getReachableRoles(array $roles)
    {
        return $this->getHierarchy()->getReachableRoles($roles);
    }

    public function getReachableRoleNames(array $roles): array
    {
        return $this->getHierarchy()->getReachableRoleNames($roles);
    }
}
