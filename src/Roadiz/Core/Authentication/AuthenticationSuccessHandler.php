<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Authentication;

use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Core\Authentication\Manager\LoginAttemptManager;
use RZ\Roadiz\Core\Entities\User;
use RZ\Roadiz\Core\Kernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationSuccessHandler;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\RememberMe\RememberMeServicesInterface;

/**
 * {@inheritdoc}
 */
class AuthenticationSuccessHandler extends DefaultAuthenticationSuccessHandler implements LoginAttemptAwareInterface
{
    protected $em;
    protected $rememberMeServices;
    private $loginAttemptManager;

    /**
     * Constructor.
     *
     * @param HttpUtils $httpUtils
     * @param EntityManager $em
     * @param RememberMeServicesInterface $rememberMeServices
     * @param array $options Options for processing a successful authentication attempt.
     */
    public function __construct(
        HttpUtils $httpUtils,
        EntityManager $em,
        RememberMeServicesInterface $rememberMeServices = null,
        array $options = []
    ) {
        parent::__construct($httpUtils, $options);
        $this->em = $em;
        $this->rememberMeServices = $rememberMeServices;

        /*
         * Enable session based _target_url
         */
        $this->setProviderKey(Kernel::SECURITY_DOMAIN);
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
            }
            $this->em->flush();
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
