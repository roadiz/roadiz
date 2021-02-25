<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Console\Helper;

use RZ\Roadiz\Core\Bags\Roles;
use RZ\Roadiz\Core\Entities\Role;
use Symfony\Component\Console\Helper\Helper;

class RolesBagHelper extends Helper
{
    protected Roles $rolesBag;

    /**
     * @param Roles $rolesBag
     */
    public function __construct(Roles $rolesBag)
    {
        $this->rolesBag = $rolesBag;
    }

    /**
     * @param string $roleName
     *
     * @return Role|null
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function get(string $roleName): ?Role
    {
        return $this->rolesBag->get($roleName);
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return 'rolesBag';
    }
}
