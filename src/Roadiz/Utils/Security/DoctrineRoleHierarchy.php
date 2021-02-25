<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Security;

use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\Core\Entities\Role;
use Symfony\Component\Security\Core\Role\RoleHierarchy;

/**
 * @package RZ\Roadiz\Utils\Security
 */
class DoctrineRoleHierarchy extends RoleHierarchy
{
    /**
     * @param EntityManagerInterface|null $em
     */
    public function __construct(EntityManagerInterface $em = null)
    {
        if (null !== $em) {
            $roleRepository = $em->getRepository(Role::class);
            $hierarchy = [
                Role::ROLE_SUPERADMIN => $roleRepository->getAllBasicRoleName(),
                Role::ROLE_BACKEND_USER => ['IS_AUTHENTICATED_ANONYMOUSLY'],
                Role::ROLE_DEFAULT => ['IS_AUTHENTICATED_ANONYMOUSLY'],
            ];
            parent::__construct($hierarchy);
        } else {
            parent::__construct([
                Role::ROLE_SUPERADMIN => [Role::ROLE_BACKEND_USER, Role::ROLE_DEFAULT],
                Role::ROLE_BACKEND_USER => ['IS_AUTHENTICATED_ANONYMOUSLY'],
                Role::ROLE_DEFAULT => ['IS_AUTHENTICATED_ANONYMOUSLY'],
            ]);
        }
    }
}
