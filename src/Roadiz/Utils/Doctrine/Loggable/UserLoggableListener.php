<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Doctrine\Loggable;

use Gedmo\Loggable\LoggableListener;
use RZ\Roadiz\Core\Entities\User;
use RZ\Roadiz\Core\Entities\UserLogEntry;

class UserLoggableListener extends LoggableListener
{
    /** @var User */
    protected $user = null;

    /**
     * @return User
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * @param User $user
     *
     * @return UserLoggableListener
     */
    public function setUser(?User $user): UserLoggableListener
    {
        $this->user = $user;
        if (null !== $user) {
            $this->setUsername($user->getUsername());
        }

        return $this;
    }

    /**
     * Handle any custom LogEntry functionality that needs to be performed
     * before persisting it
     *
     * @param object $logEntry The LogEntry being persisted
     * @param object $object The object being Logged
     */
    protected function prePersistLogEntry($logEntry, $object)
    {
        parent::prePersistLogEntry($logEntry, $object);

        if ($logEntry instanceof UserLogEntry) {
            $logEntry->setUser($this->getUser());
        }
    }
}
