<?php
declare(strict_types=1);

namespace Themes\Rozier\Explorer;

use RZ\Roadiz\Core\Entities\User;

final class UsersProvider extends AbstractDoctrineExplorerProvider
{
    protected function getProvidedClassname(): string
    {
        return User::class;
    }

    protected function getDefaultCriteria(): array
    {
        return [];
    }

    protected function getDefaultOrdering(): array
    {
        return ['username' =>'ASC'];
    }

    /**
     * @inheritDoc
     */
    public function supports($item)
    {
        if ($item instanceof User) {
            return true;
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function toExplorerItem($item)
    {
        if ($item instanceof User) {
            return new UserExplorerItem($item);
        }

        return null;
    }
}
