<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Authentication;

use Doctrine\Persistence\ManagerRegistry;
use RZ\Roadiz\Core\Authentication\Manager\LoginAttemptManager;
use RZ\Roadiz\Core\Entities\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationSuccessHandler;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\RememberMe\RememberMeServicesInterface;

class AuthenticationSuccessHandler extends DefaultAuthenticationSuccessHandler implements LoginAttemptAwareInterface
{
    protected ?RememberMeServicesInterface $rememberMeServices;
    protected ?LoginAttemptManager $loginAttemptManager = null;
    private ManagerRegistry $managerRegistry;

    /**
     * @param HttpUtils $httpUtils
     * @param ManagerRegistry $managerRegistry
     * @param ?RememberMeServicesInterface $rememberMeServices
     * @param array $options Options for processing a successful authentication attempt.
     * @param string $providerKey
     */
    public function __construct(
        HttpUtils $httpUtils,
        ManagerRegistry $managerRegistry,
        RememberMeServicesInterface $rememberMeServices = null,
        array $options = [],
        string $providerKey = 'roadiz_domain'
    ) {
        parent::__construct($httpUtils, $options);
        $this->rememberMeServices = $rememberMeServices;
        $this->managerRegistry = $managerRegistry;

        /*
         * Enable session based _target_url
         */
        $this->setProviderKey($providerKey);
    }

    /**
     * {@inheritdoc}
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token)
    {
        $user = $token->getUser();
        if (null !== $user && $user instanceof UserInterface) {
            $this->getLoginAttemptManager()->onSuccessLoginAttempt($user->getUsername());
            if ($user instanceof User) {
                $user->setLastLogin(new \DateTime('now'));
                $manager = $this->managerRegistry->getManagerForClass(User::class);
                if (null !== $manager) {
                    $manager->flush();
                }
            }
        }

        $response = parent::onAuthenticationSuccess($request, $token);

        if (null !== $this->rememberMeServices) {
            $this->rememberMeServices->loginSuccess($request, $response, $token);
        }

        return $response;
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
