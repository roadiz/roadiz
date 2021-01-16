<?php
declare(strict_types=1);

namespace Themes\Rozier\Controllers\Attributes;

use RZ\Roadiz\Attribute\Form\AttributeGroupType;
use RZ\Roadiz\Core\AbstractEntities\PersistableInterface;
use RZ\Roadiz\Core\Entities\AttributeGroup;
use RZ\Roadiz\Core\Entities\SettingGroup;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\String\UnicodeString;
use Themes\Rozier\Controllers\AbstractAdminController;
use Themes\Rozier\RozierApp;

/**
 * @package Themes\Rozier\Controllers\Attributes
 */
class AttributeGroupController extends AbstractAdminController
{
    /**
     * @inheritDoc
     */
    protected function supports(PersistableInterface $item): bool
    {
        return $item instanceof AttributeGroup;
    }

    /**
     * @inheritDoc
     */
    protected function getNamespace(): string
    {
        return 'attribute_group';
    }

    /**
     * @inheritDoc
     */
    protected function createEmptyItem(Request $request): PersistableInterface
    {
        return new AttributeGroup();
    }

    /**
     * @inheritDoc
     */
    protected function getTemplateFolder(): string
    {
        return 'attributes/groups';
    }

    /**
     * @inheritDoc
     */
    protected function getRequiredRole(): string
    {
        return 'ROLE_ACCESS_ATTRIBUTES';
    }

    /**
     * @inheritDoc
     */
    protected function getEntityClass(): string
    {
        return AttributeGroup::class;
    }

    /**
     * @inheritDoc
     */
    protected function getFormType(): string
    {
        return AttributeGroupType::class;
    }

    /**
     * @inheritDoc
     */
    protected function getDefaultRouteName(): string
    {
        return 'attributeGroupsHomePage';
    }

    /**
     * @inheritDoc
     */
    protected function getEditRouteName(): string
    {
        return 'attributeGroupsEditPage';
    }

    /**
     * @inheritDoc
     */
    protected function getEntityName(PersistableInterface $item): string
    {
        if ($item instanceof AttributeGroup) {
            return $item->getName();
        }
        throw new \InvalidArgumentException('Item should be instance of '.$this->getEntityClass());
    }

    /**
     * @inheritDoc
     */
    protected function getDefaultOrder(): array
    {
        return ['canonicalName' => 'ASC'];
    }
}
