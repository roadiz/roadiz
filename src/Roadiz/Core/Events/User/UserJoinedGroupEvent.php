<?php

declare(strict_types=1);

namespace RZ\Roadiz\Core\Events\User;

use RZ\Roadiz\Core\Entities\Group;
use RZ\Roadiz\Core\Entities\User;
use RZ\Roadiz\Core\Events\FilterUserEvent;

final class UserJoinedGroupEvent extends FilterUserEvent
{
    /**
     * @var Group
     */
    private $group;

    public function __construct(User $user, Group $group)
    {
        parent::__construct($user);
        $this->group = $group;
    }

    /**
     * @return Group
     */
    public function getGroup(): Group
    {
        return $this->group;
    }
}
