<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Authorization\Chroot;

use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\User;

/**
 * Classic Roadiz User chroot from Doctrine relation.
 *
 * @package RZ\Roadiz\Core\Authorization\Chroot
 */
class RoadizUserNodeChrootResolver implements NodeChrootResolver
{
    public function supports($user): bool
    {
        return $user instanceof User;
    }

    /**
     * @param User $user
     *
     * @return Node|null
     */
    public function getChroot($user): ?Node
    {
        return $user->getChroot();
    }
}
