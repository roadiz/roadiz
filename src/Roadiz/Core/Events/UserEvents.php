<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Events;

use RZ\Roadiz\Core\Events\User\UserCreatedEvent;
use RZ\Roadiz\Core\Events\User\UserDeletedEvent;
use RZ\Roadiz\Core\Events\User\UserDisabledEvent;
use RZ\Roadiz\Core\Events\User\UserEnabledEvent;
use RZ\Roadiz\Core\Events\User\UserPasswordChangedEvent;
use RZ\Roadiz\Core\Events\User\UserUpdatedEvent;

/**
 * @deprecated
 */
final class UserEvents
{
    /**
     * Event user.created is triggered each time a user
     * is created.
     *
     * Event listener will be given a:
     * RZ\Roadiz\Core\Events\FilterUserEvent instance
     *
     * @var string
     * @deprecated
     */
    const USER_CREATED = UserCreatedEvent::class;

    /**
     * Event user.updated is triggered each time a user
     * is updated.
     *
     * Event listener will be given a:
     * RZ\Roadiz\Core\Events\FilterUserEvent instance
     *
     * @var string
     * @deprecated
     */
    const USER_UPDATED = UserUpdatedEvent::class;

    /**
     * Event user.deleted is triggered each time a user
     * is deleted.
     *
     * Event listener will be given a:
     * RZ\Roadiz\Core\Events\FilterUserEvent instance
     *
     * @var string
     * @deprecated
     */
    const USER_DELETED = UserDeletedEvent::class;

    /**
     * Event user.enabled is triggered each time a user
     * is enabled.
     *
     * Event listener will be given a:
     * RZ\Roadiz\Core\Events\FilterUserEvent instance
     *
     * @var string
     * @deprecated
     */
    const USER_ENABLED = UserEnabledEvent::class;

    /**
     * Event user.disabled is triggered each time a user
     * is disabled.
     *
     * Event listener will be given a:
     * RZ\Roadiz\Core\Events\FilterUserEvent instance
     *
     * @var string
     * @deprecated
     */
    const USER_DISABLED = UserDisabledEvent::class;

    /**
     * Event user.password_changed is triggered each time a user
     * has its password changed.
     *
     * Event listener will be given a:
     * RZ\Roadiz\Core\Events\FilterUserEvent instance
     *
     * @var string
     * @deprecated
     */
    const USER_PASSWORD_CHANGED = UserPasswordChangedEvent::class;
}
