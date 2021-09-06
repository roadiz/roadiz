<?php
declare(strict_types=1);

namespace Themes\Rozier\Explorer;

use RZ\Roadiz\Core\Entities\User;
use RZ\Roadiz\Explorer\AbstractExplorerItem;

final class UserExplorerItem extends AbstractExplorerItem
{
    private User $user;

    /**
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * @inheritDoc
     */
    public function getId()
    {
        return $this->user->getId();
    }

    /**
     * @inheritDoc
     */
    public function getAlternativeDisplayable(): ?string
    {
        return $this->user->getEmail();
    }

    /**
     * @inheritDoc
     */
    public function getDisplayable(): string
    {
        $fullName = trim($this->user->getFirstName() ?? '' . ' ' . $this->user->getLastName() ?? '');
        if ($fullName !== '') {
            return $fullName;
        }
        return $this->user->getUsername();
    }

    /**
     * @inheritDoc
     */
    public function getOriginal()
    {
        return $this->user;
    }
}
