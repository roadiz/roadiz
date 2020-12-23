<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Doctrine\Loggable;

use Gedmo\Loggable\LoggableListener;
use RZ\Roadiz\Core\Entities\User;
use RZ\Roadiz\Core\Entities\UserLogEntry;
use Symfony\Component\Security\Core\User\UserInterface;

class UserLoggableListener extends LoggableListener
{
    /** @var UserInterface|null */
    protected $user = null;

    /**
     * @return UserInterface|null
     */
    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    /**
     * @param UserInterface|null $user
     *
     * @return UserLoggableListener
     */
    public function setUser(?UserInterface $user): UserLoggableListener
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

        $user = $this->getUser();
        if ($logEntry instanceof UserLogEntry && $user instanceof User) {
            $logEntry->setUser($user);
        }
    }
}
