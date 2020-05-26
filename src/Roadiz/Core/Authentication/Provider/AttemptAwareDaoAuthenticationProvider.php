<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Authentication\Provider;

use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\Core\Authentication\LoginAttemptAwareInterface;
use RZ\Roadiz\Core\Authentication\Manager\LoginAttemptManager;
use Symfony\Component\Security\Core\Authentication\Provider\DaoAuthenticationProvider;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class AttemptAwareDaoAuthenticationProvider extends DaoAuthenticationProvider implements LoginAttemptAwareInterface
{
    /**
     * @var LoginAttemptManager
     */
    protected $loginAttemptManager;

    /**
     * AttemptAwareDaoAuthenticationProvider constructor.
     *
     * @param EntityManagerInterface  $entityManager
     * @param UserProviderInterface   $userProvider
     * @param UserCheckerInterface    $userChecker
     * @param string                  $providerKey
     * @param EncoderFactoryInterface $encoderFactory
     * @param bool                    $hideUserNotFoundExceptions
     */
    public function __construct(
        LoginAttemptManager $loginAttemptManager,
        UserProviderInterface $userProvider,
        UserCheckerInterface $userChecker,
        string $providerKey,
        EncoderFactoryInterface $encoderFactory,
        bool $hideUserNotFoundExceptions = true
    ) {
        parent::__construct($userProvider, $userChecker, $providerKey, $encoderFactory, $hideUserNotFoundExceptions);
        $this->loginAttemptManager = $loginAttemptManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function retrieveUser($username, UsernamePasswordToken $token)
    {
        $this->getLoginAttemptManager()->checkLoginAttempts($username);
        return parent::retrieveUser($username, $token);
    }

    /**
     * @return LoginAttemptManager
     */
    public function getLoginAttemptManager(): LoginAttemptManager
    {
        return $this->loginAttemptManager;
    }

    /**
     * @param LoginAttemptManager $loginAttemptManager
     *
     * @return AttemptAwareDaoAuthenticationProvider
     */
    public function setLoginAttemptManager(
        LoginAttemptManager $loginAttemptManager
    ): AttemptAwareDaoAuthenticationProvider {
        $this->loginAttemptManager = $loginAttemptManager;

        return $this;
    }
}
