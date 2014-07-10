<?php 
/**
 * Copyright REZO ZERO 2014
 * 
 * 
 * 
 *
 * @file BackendController.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\CMS\Controllers;

use RZ\Renzo\Core\Kernel;

use RZ\Renzo\Core\Handlers\UserProvider;
use RZ\Renzo\Core\Handlers\UserHandler;
use Symfony\Component\Security\Http\HttpUtils;


use Symfony\Component\Security\Http\Firewall;
use Symfony\Component\Security\Http\FirewallMap;
use Symfony\Component\Security\Http\Firewall\ExceptionListener;
use Symfony\Component\Security\Http\Firewall\UsernamePasswordFormAuthenticationListener;
use Symfony\Component\Security\Http\Session\SessionAuthenticationStrategy;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolver;

use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationSuccessHandler;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationFailureHandler;

use Symfony\Component\Security\Core\Authentication\Provider\DaoAuthenticationProvider;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserChecker;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManager;
use Symfony\Component\Security\Core\Authorization\Voter\RoleVoter;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

/**
 * Special controller app file for backend themes
 * 
 * This AppController implementation will use a security scheme
 * 
 */
class BackendController extends AppController {
	
	protected static $backendTheme = true;

	/**
	 * Check if twig cache must be cleared 
	 */
	protected function handleTwigCache() {

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
	 * Register current AppController security scheme in Kernel firewall map
	 * 
	 * @param FirewallMap $firewallMap
	 * @param HttpKernelInterface $httpKernel
	 * @param HttpUtils $httpUtils
	 */
	public static function appendToFirewallMap( FirewallMap $firewallMap, HttpKernelInterface $httpKernel, HttpUtils $httpUtils )
	{
		/*
		 * Need session for security
		 */
		static::initializeSession();

		$authenticationManager = new DaoAuthenticationProvider(
			new UserProvider(),
			new UserChecker(),
			'rz-admin',
			UserHandler::getEncoderFactory()
		);
		$accessDecisionManager = new AccessDecisionManager(
			array(
				new RoleVoter()
			)
		);
		static::$securityContext = new SecurityContext(
			$authenticationManager,
			$accessDecisionManager
		);

		/*
		 * Listener
		 */
		$usernamePasswordListener = new UsernamePasswordFormAuthenticationListener(
			static::getSecurityContext(), 
			$authenticationManager, 
			new SessionAuthenticationStrategy(SessionAuthenticationStrategy::INVALIDATE), 
			$httpUtils, 
			'rz-admin',
			new DefaultAuthenticationSuccessHandler($httpUtils, array(
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
			null, 
			null, 
			null //csrfTokenManager
		);

		/*
		 * Prepare app firewall
		 */
		$requestMatcher = new RequestMatcher('^/rz-admin');  

		// instances of Symfony\Component\Security\Http\Firewall\ListenerInterface
		$listeners = array(
			$usernamePasswordListener
		);
		$exceptionListener = new ExceptionListener(
			static::getSecurityContext(), 
			new AuthenticationTrustResolver('ROLE_ANONYMOUS', 'ROLE_REMEMBER_ME'), 
			$httpUtils, 
			'rz-admin',
			new \Symfony\Component\Security\Http\EntryPoint\FormAuthenticationEntryPoint(
				$httpKernel, 
				$httpUtils, 
				'/login', 
				true // bool $useForward
			),
			null, //$errorPage
			null, //AccessDeniedHandlerInterface $accessDeniedHandler
			null //LoggerInterface $logger
		);

		/*
		 * Inject a new firewall map element
		 */
		$firewallMap->add($requestMatcher, $listeners, $exceptionListener);
	}
}