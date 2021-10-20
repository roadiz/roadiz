<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Authentication;

use RZ\Roadiz\Core\Authentication\Manager\LoginAttemptManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationFailureHandler;

class AuthenticationFailureHandler extends DefaultAuthenticationFailureHandler implements LoginAttemptAwareInterface
{
    protected ?LoginAttemptManager $loginAttemptManager = null;

    /**
     * {@inheritdoc}
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $username = $request->request->get('_username');
        $ipAddress = $request->getClientIp();
        if (null !== $this->logger) {
            $this->logger->error($exception->getMessage(), [
                'username' => $username,
                'ipAddress' => $ipAddress
            ]);
        }
        if (is_string($username) && $exception instanceof BadCredentialsException) {
            $this->getLoginAttemptManager()->onFailedLoginAttempt($username);
        }

        return parent::onAuthenticationFailure($request, $exception);
    }

    /**
     * @inheritDoc
     */
    public function getLoginAttemptManager(): LoginAttemptManager
    {
        if (null === $this->loginAttemptManager) {
            throw new \InvalidArgumentException('LoginAttemptManager should not be null');
        }
        return $this->loginAttemptManager;
    }

    /**
     * @inheritDoc
     */
    public function setLoginAttemptManager(LoginAttemptManager $loginAttemptManager)
    {
        $this->loginAttemptManager = $loginAttemptManager;
        return $this;
    }
}
