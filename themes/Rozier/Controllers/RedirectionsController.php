<?php
declare(strict_types=1);

namespace Themes\Rozier\Controllers;

use RZ\Roadiz\Core\AbstractEntities\PersistableInterface;
use RZ\Roadiz\Core\Entities\Redirection;
use Symfony\Component\HttpFoundation\Request;
use Themes\Rozier\Forms\RedirectionType;

/**
 * @package Themes\Rozier\Controllers
 */
class RedirectionsController extends AbstractAdminController
{
    /**
     * @inheritDoc
     */
    protected function supports(PersistableInterface $item): bool
    {
        return $item instanceof Redirection;
    }

    /**
     * @inheritDoc
     */
    protected function getNamespace(): string
    {
        return 'redirection';
    }

    /**
     * @inheritDoc
     */
    protected function createEmptyItem(Request $request): PersistableInterface
    {
        return new Redirection();
    }

    /**
     * @inheritDoc
     */
    protected function getTemplateFolder(): string
    {
        return 'redirections';
    }

    /**
     * @inheritDoc
     */
    protected function getRequiredRole(): string
    {
        return 'ROLE_ACCESS_REDIRECTIONS';
    }

    /**
     * @inheritDoc
     */
    protected function getEntityClass(): string
    {
        return Redirection::class;
    }

    /**
     * @inheritDoc
     */
    protected function getFormType(): string
    {
        return RedirectionType::class;
    }

    /**
     * @inheritDoc
     */
    protected function getDefaultRouteName(): string
    {
        return 'redirectionsHomePage';
    }

    /**
     * @inheritDoc
     */
    protected function getEditRouteName(): string
    {
        return 'redirectionsEditPage';
    }

    /**
     * @inheritDoc
     */
    protected function getEntityName(PersistableInterface $item): string
    {
        if ($item instanceof Redirection) {
            return (string) $item->getQuery();
        }
        throw new \InvalidArgumentException('Item should be instance of '.$this->getEntityClass());
    }

    /**
     * @inheritDoc
     */
    protected function getDefaultOrder(): array
    {
        return ['query' => 'ASC'];
    }
}
