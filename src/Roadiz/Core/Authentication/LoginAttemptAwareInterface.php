<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Authentication;

use RZ\Roadiz\Core\Authentication\Manager\LoginAttemptManager;

interface LoginAttemptAwareInterface
{
    /**
     * @return LoginAttemptManager
     */
    public function getLoginAttemptManager(): LoginAttemptManager;

    /**
     * @param LoginAttemptManager $loginAttemptManager
     * @return $this
     */
    public function setLoginAttemptManager(LoginAttemptManager $loginAttemptManager);
}
