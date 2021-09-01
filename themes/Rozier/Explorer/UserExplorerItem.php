<?php
declare(strict_types=1);

namespace Themes\Rozier\Explorer;

use RZ\Roadiz\Core\Entities\User;

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
    public function getAlternativeDisplayable()
    {
        return null !== ($this->user) ? ($this->user->getEmail() ?: '') : ('');
    }

    /**
     * @inheritDoc
     */
    public function getDisplayable()
    {
        $fullName = trim((string) $this->user->getFirstName() . ' ' . (string) $this->user->getLastName());
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
