<?php
/**
 * Copyright REZO ZERO 2014
 *
 * @file BackendController.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\CMS\Controllers;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Log\Logger;
use RZ\Renzo\Core\Entities\Role;
use RZ\Renzo\Core\Handlers\UserProvider;
use RZ\Renzo\Core\Handlers\UserHandler;
use RZ\Renzo\Core\Authentification\AuthenticationSuccessHandler;
use RZ\Renzo\Core\Authorization\AccessDeniedHandler;


use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\Firewall;
use Symfony\Component\Security\Http\FirewallMap;
use Symfony\Component\Security\Http\Firewall\ExceptionListener;
use Symfony\Component\Security\Http\Firewall\UsernamePasswordFormAuthenticationListener;
use Symfony\Component\Security\Http\Firewall\ContextListener;
use Symfony\Component\Security\Http\Firewall\LogoutListener;
use Symfony\Component\Security\Http\Firewall\AccessListener;
use Symfony\Component\Security\Http\Firewall\AnonymousAuthenticationListener;
use Symfony\Component\Security\Http\Session\SessionAuthenticationStrategy;
use Symfony\Component\Security\Http\AccessMap;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolver;

use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationFailureHandler;
use Symfony\Component\Security\Http\Logout\DefaultLogoutSuccessHandler;

use Symfony\Component\Security\Core\Authentication\Provider\DaoAuthenticationProvider;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserChecker;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManager;
use Symfony\Component\Security\Core\Authorization\Voter\RoleVoter;
use Symfony\Component\EventDispatcher\EventDispatcher;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

/**
 * Special controller app file for backend themes.
 *
 * This AppController implementation will use a security scheme
 *
 */
class BackendController extends AppController
{
    protected static $backendTheme = true;

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        parent::__construct();
        $this->logger->setSecurityContext(static::$securityContext);
    }

    /**
     * {@inheritdoc}
     */
    public function handleTwigCache()
    {

        if (Kernel::getInstance()->isBackendDebug()) {
            try {
                $fs = new Filesystem();
                $fs->remove(array($this->getCacheDirectory()));
            } catch (IOExceptionInterface $e) {
                echo "An error occurred while deleting backend twig cache directory: ".$e->getPath();
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function appendToFirewallMap(
        FirewallMap $firewallMap,
        HttpKernelInterface $httpKernel,
        HttpUtils $httpUtils,
        EventDispatcher $dispatcher = null
    )
    {
        /*
         * Need session for security
         */
        static::initializeSession();

        $areaName = 'rz_admin';

        $renzoUserProvider = new UserProvider();

        $authenticationManager = new DaoAuthenticationProvider(
            $renzoUserProvider,
            new UserChecker(),
            $areaName,
            UserHandler::getEncoderFactory()
        );
        $accessDecisionManager = new AccessDecisionManager(
            array(
                new RoleVoter('ROLE_')
            )
        );
        static::$securityContext = new SecurityContext(
            $authenticationManager,
            $accessDecisionManager
        );


        /*
         * Prepare app firewall
         */
        $requestMatcher = new RequestMatcher('^/rz-admin');
        // allows configuration of different access control rules for specific parts of the website.
        $accessMap = new AccessMap($requestMatcher, array(Role::ROLE_BACKEND_USER));
        $accessMap->add(new RequestMatcher('^/rz-admin'), array(Role::ROLE_BACKEND_USER));

        /*
         * Listener
         */
        $logoutListener = new LogoutListener(
            static::getSecurityContext(),
            $httpUtils,
            //Symfony\Component\Security\Http\Logout\SessionLogoutHandler
            new DefaultLogoutSuccessHandler($httpUtils),
            array(
                'logout_path'    => '/rz-admin/logout',
            )
        );
        //Symfony\Component\Security\Http\Logout\SessionLogoutHandler
        $logoutListener->addHandler(new \Symfony\Component\Security\Http\Logout\SessionLogoutHandler());

        $listeners = array(
            // manages the SecurityContext persistence through a session
            new ContextListener(
                static::getSecurityContext(),
                array($renzoUserProvider),
                $areaName,
                new Logger(),
                $dispatcher
            ),
            // logout users
            $logoutListener,
            // authentication via a simple form composed of a username and a password
            new UsernamePasswordFormAuthenticationListener(
                static::getSecurityContext(),
                $authenticationManager,
                new SessionAuthenticationStrategy(SessionAuthenticationStrategy::INVALIDATE),
                $httpUtils,
                $areaName,
                new AuthenticationSuccessHandler($httpUtils, array(
                    'always_use_default_target_path' => false,
                    'default_target_path'            => '/rz-admin',
                    'login_path'                     => '/login',
                    'target_path_parameter'          => '_target_path',
                    'use_referer'                    => false,
                )),
                new DefaultAuthenticationFailureHandler($httpKernel, $httpUtils, array(
                    'failure_path'           => '/login_failed',
                    'failure_forward'        => false,
                    'login_path'             => '/login',
                    'failure_path_parameter' => '_failure_path'
                )),
                array(
                    'check_path' => '/rz-admin/login_check',
                ),
                new Logger(), // A LoggerInterface instance
                $dispatcher,
                null //csrfTokenManager
            ),
            // enforces access control rules
            new AccessListener(
                static::getSecurityContext(),
                $accessDecisionManager,
                $accessMap,
                $authenticationManager
            ),
            // automatically adds a Token if none is already present.
            //new AnonymousAuthenticationListener(static::getSecurityContext(), '') // $key
        );

        $exceptionListener = new ExceptionListener(
            static::getSecurityContext(),
            new AuthenticationTrustResolver('', ''),
            $httpUtils,
            $areaName,
            new \Symfony\Component\Security\Http\EntryPoint\FormAuthenticationEntryPoint(
                $httpKernel,
                $httpUtils,
                '/login',
                true // bool $useForward
            ),
            null, //$errorPage
            new AccessDeniedHandler(), //AccessDeniedHandlerInterface $accessDeniedHandler
            new Logger() //LoggerInterface $logger
        );

        /*
         * Inject a new firewall map element
         */
        $firewallMap->add($requestMatcher, $listeners, $exceptionListener);
    }
}