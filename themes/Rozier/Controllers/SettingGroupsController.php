<?php
declare(strict_types=1);

namespace Themes\Rozier\Controllers;

use RZ\Roadiz\Core\AbstractEntities\PersistableInterface;
use RZ\Roadiz\Core\Entities\SettingGroup;
use Symfony\Component\HttpFoundation\Request;
use Themes\Rozier\Forms\SettingGroupType;

class SettingGroupsController extends AbstractAdminController
{
    /**
     * @inheritDoc
     */
    protected function supports(PersistableInterface $item): bool
    {
        return $item instanceof SettingGroup;
    }

    /**
     * @inheritDoc
     */
    protected function getNamespace(): string
    {
        return 'settingGroup';
    }

    /**
     * @inheritDoc
     */
    protected function createEmptyItem(Request $request): PersistableInterface
    {
        return new SettingGroup();
    }

    /**
     * @inheritDoc
     */
    protected function getTemplateFolder(): string
    {
        return 'settingGroups';
    }

    /**
     * @inheritDoc
     */
    protected function getRequiredRole(): string
    {
        return 'ROLE_ACCESS_SETTINGS';
    }

    /**
     * @inheritDoc
     */
    protected function getEntityClass(): string
    {
        return SettingGroup::class;
    }

    /**
     * @inheritDoc
     */
    protected function getFormType(): string
    {
        return SettingGroupType::class;
    }

    /**
     * @inheritDoc
     */
    protected function getDefaultRouteName(): string
    {
        return 'settingGroupsHomePage';
    }

    /**
     * @inheritDoc
     */
    protected function getEditRouteName(): string
    {
        return 'settingGroupsEditPage';
    }

    /**
     * @inheritDoc
     */
    protected function getEntityName(PersistableInterface $item): string
    {
        if ($item instanceof SettingGroup) {
            return $item->getName();
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
}
