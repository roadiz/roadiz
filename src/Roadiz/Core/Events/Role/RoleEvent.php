<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Events\Role;

use RZ\Roadiz\Core\Entities\Role;
use Symfony\Contracts\EventDispatcher\Event;

abstract class RoleEvent extends Event
{
    /**
     * @var Role|null
     */
    protected $role;

    /**
     * @param Role|null $role
     */
    public function __construct(?Role $role)
    {
        $this->role = $role;
    }

    /**
     * @return Role|null
     */
    public function getRole(): ?Role
    {
        return $this->role;
    }

    /**
     * @param Role|null $role
     * @return RoleEvent
     */
    public function setRole(?Role $role): RoleEvent
    {
        $this->role = $role;
        return $this;
    }
}
