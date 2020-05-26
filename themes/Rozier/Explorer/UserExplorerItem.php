<?php
declare(strict_types=1);

namespace Themes\Rozier\Explorer;

use RZ\Roadiz\Core\Entities\User;

class UserExplorerItem extends AbstractExplorerItem
{
    /**
     * @var User
     */
    private $user;

    /**
     * UserExplorerItem constructor.
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
        return $this->user->getEmail();
    }

    /**
     * @inheritDoc
     */
    public function getDisplayable()
    {
        if (trim($this->user->getFirstName() . ' ' . $this->user->getLastName()) !== '') {
            return trim($this->user->getFirstName() . ' ' . $this->user->getLastName());
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
