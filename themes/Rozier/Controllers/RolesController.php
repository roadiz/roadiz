<?php
declare(strict_types=1);

namespace Themes\Rozier\Controllers;

use RZ\Roadiz\Core\AbstractEntities\PersistableInterface;
use RZ\Roadiz\Core\Entities\Role;
use RZ\Roadiz\Core\Events\Role\PreCreatedRoleEvent;
use RZ\Roadiz\Core\Events\Role\PreDeletedRoleEvent;
use RZ\Roadiz\Core\Events\Role\PreUpdatedRoleEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;
use Themes\Rozier\Forms\RoleType;

/**
 * @package Themes\Rozier\Controllers
 */
class RolesController extends AbstractAdminController
{
    /**
     * @inheritDoc
     */
    protected function supports(PersistableInterface $item): bool
    {
        return $item instanceof Role;
    }

    /**
     * @inheritDoc
     */
    protected function getNamespace(): string
    {
        return 'role';
    }

    /**
     * @inheritDoc
     */
    protected function createEmptyItem(Request $request): PersistableInterface
    {
        return new Role('ROLE_EXAMPLE');
    }

    /**
     * @inheritDoc
     */
    protected function getTemplateFolder(): string
    {
        return 'roles';
    }

    /**
     * @inheritDoc
     */
    protected function getRequiredRole(): string
    {
        return 'ROLE_ACCESS_ROLES';
    }

    /**
     * @inheritDoc
     */
    protected function getEntityClass(): string
    {
        return Role::class;
    }

    /**
     * @inheritDoc
     */
    protected function getFormType(): string
    {
        return RoleType::class;
    }

    /**
     * @inheritDoc
     */
    protected function getDefaultRouteName(): string
    {
        return 'rolesHomePage';
    }

    /**
     * @inheritDoc
     */
    protected function getEditRouteName(): string
    {
        return 'rolesEditPage';
    }

    /**
     * @inheritDoc
     */
    protected function getEntityName(PersistableInterface $item): string
    {
        if ($item instanceof Role) {
            return $item->getRole();
        }
        throw new \InvalidArgumentException('Item should be instance of '.$this->getEntityClass());
    }

    /**
     * @inheritDoc
     */
    protected function getDefaultOrder(): array
    {
        return ['name' => 'ASC'];
    }

    /**
     * @inheritDoc
     */
    protected function denyAccessUnlessItemGranted(PersistableInterface $item): void
    {
        if ($item instanceof Role) {
            $this->denyAccessUnlessGranted($item->getRole());
        }
    }

    /**
     * @inheritDoc
     */
    protected function createCreateEvent(PersistableInterface $item): ?Event
    {
        if ($item instanceof Role) {
            return new PreCreatedRoleEvent($item);
        }
        return null;
    }

    /**
     * @inheritDoc
     */
    protected function createUpdateEvent(PersistableInterface $item): ?Event
    {
        if ($item instanceof Role) {
            return new PreUpdatedRoleEvent($item);
        }
        return null;
    }

    /**
     * @inheritDoc
     */
    protected function createDeleteEvent(PersistableInterface $item): ?Event
    {
        if ($item instanceof Role) {
            return new PreDeletedRoleEvent($item);
        }
        return null;
    }
}
