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
use RZ\Renzo\Core\Entities\Theme;
use RZ\Renzo\Core\Entities\Document;

use RZ\Renzo\Core\Viewers\ViewableInterface;

use Symfony\Component\Security\Http\Firewall;
use Symfony\Component\Security\Http\FirewallMap;
use Symfony\Component\Security\Http\HttpUtils;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\EventDispatcher\EventDispatcher;

use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Extension\Csrf\CsrfExtension;
use Symfony\Component\Form\Extension\Csrf\CsrfProvider\SessionCsrfProvider;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Extension\RoutingExtension;
use Symfony\Bridge\Twig\Form\TwigRenderer;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\Loader\XliffFileLoader;
use Symfony\Bridge\Twig\Extension\TranslationExtension;



/**
 * Base class for Renzo themes
 */
class AppController implements ViewableInterface {
	
	/**
	 * Theme name
	 * @var string
	 */
	protected static $themeName =      '';
	/**
	 * @return string
	 */
	public static function getThemeName() {
	    return static::$themeName;
	}

	/**
	 * Theme author description
	 * @var string
	 */
	protected static $themeAuthor =    '';
	/**
	 * @return string
	 */
	public static function getThemeAuthor() {
	    return static::$themeAuthor;
	}

	/**
	 * Theme copyright licence
	 * @var string
	 */
	protected static $themeCopyright = '';
	/**
	 * @return string
	 */
	public static function getThemeCopyright() {
	    return static::$themeCopyright;
	}

	/**
	 * Theme base directory name
	 * Example: "MyTheme" will be located in "themes/MyTheme"
	 * @var string
	 */
	protected static $themeDir =       '';
	/**
	 * @return string
	 */
	public static function getThemeDir() {
	    return static::$themeDir;
	}

	/**
	 * Is theme for backend?
	 * @var boolean
	 */
	protected static $backendTheme = false;
	/**
	 * @return boolean
	 */
	public static function isBackendTheme() {
	    return static::$backendTheme;
	}

	/**
	 * Twig environment instance
	 * @var \Twig_Environment
	 */
	protected $twig = null;
	/**
	 * Assignation for twig template engine
	 * @var array
	 */
	protected $assignation =  array();
	protected static $csrfProvider = null;

	/**
	 * @var Symfony\Component\Translation\Translator
	 */
	protected $translator = null;
	/**
	 * @return Symfony\Component\Translation\Translator
	 */
	public function getTranslator()
	{
		return $this->translator;
	}

	/**
	 * @var Psr\Log\LoggerInterface
	 */
	protected $logger = null;
	/**
	 * @return Psr\Log\LoggerInterface
	 */
	public function getLogger()
	{
		return $this->logger;
	}

	public function __construct(){
		$this->initializeTwig()
			 ->initializeTranslator()
			 ->prepareBaseAssignation();

		$this->logger = new \RZ\Renzo\Core\Log\Logger();
	}
	/**
	 * 
	 * @return RouteCollection
	 */
	public static function getRoutes()
	{
		$locator = new FileLocator(array(
			RENZO_ROOT.'/themes/'.static::$themeDir.'/Resources'
		));

		if (file_exists(RENZO_ROOT.'/themes/'.static::$themeDir.'/Resources/routes.yml')) {
			$loader = new YamlFileLoader($locator);
			return $loader->load('routes.yml');
		}

		return null;
	}

	/**
	 * Create a translator instance and load theme messages
	 * 
	 * {{themeDir}}/Resources/translations/messages.{{lang}}.xlf
	 * 
	 * @todo  [Cache] Need to write XLF catalog to PHP using \Symfony\Component\Translation\Writer\TranslationWriter 
	 */
	public function initializeTranslator()
	{
		$lang = Kernel::getInstance()->getRequest()->getLocale();
		$msgPath = RENZO_ROOT.'/themes/'.static::$themeDir.'/Resources/translations/messages.'.$lang.'.xlf';

		/*
		 * fallback to english, if message catalog absent
		 */
		if (!file_exists($msgPath)) {
			$lang = 'en';
		}

		// instancier un objet de la classe Translator
		$this->translator = new Translator($lang);
		// charger, en quelque sorte, des traductions dans ce translator
		$this->translator->addLoader('xlf', new XliffFileLoader());
		$this->translator->addResource(
		    'xlf',
			RENZO_ROOT.'/themes/'.static::$themeDir.'/Resources/translations/messages.'.$lang.'.xlf',
		    $lang
		);
		// ajoutez le TranslationExtension (nous donnant les filtres trans et transChoice)
		$this->twig->addExtension(new TranslationExtension($this->translator));
		
		return $this;
	}

	/**
	 * Create session for current controller before HttpKernel handling.
	 * 
	 * @return void
	 */
	protected static function initializeSession()
	{
		// créer un objet session depuis le composant HttpFoundation
		Kernel::getInstance()->getRequest()->setSession(new Session());

		// générer le secret CSRF depuis quelque part
		$csrfSecret = Kernel::getInstance()->getConfig()["security"]['secret'];
		static::$csrfProvider = new SessionCsrfProvider(
			Kernel::getInstance()->getRequest()->getSession(), 
			$csrfSecret
		);
	}

	/**
	 * Get twig cache folder for current Theme
	 * @return string
	 */
	public function getCacheDirectory()
	{
		return RENZO_ROOT.'/cache/'.static::$themeDir.'/twig_cache';
	}

	/**
	 * Check if twig cache must be cleared 
	 */
	public function handleTwigCache() {

		if (Kernel::getInstance()->isDebug()) {
			try {
				$fs = new Filesystem();
				$fs->remove(array($this->getCacheDirectory()));
			} catch (IOExceptionInterface $e) {
			    echo "An error occurred while deleting backend twig cache directory: ".$e->getPath();
			}
		}
	}

	/**
	 * Create a Twig Environment instance
	 */
	public function initializeTwig()
	{
		$this->handleTwigCache();
		/*
		 * Enabling forms
		 */
		// le fichier Twig contenant toutes les balises pour afficher les formulaires
		// ce fichier vient avoir le TwigBridge
		$defaultFormTheme = 'form_div_layout.html.twig';

		$vendorDir = realpath(RENZO_ROOT . '/vendor');
		// le chemin vers TwigBridge pour que Twig puisse localiser
		// le fichier form_div_layout.html.twig
		$vendorTwigBridgeDir =
		    $vendorDir . '/symfony/twig-bridge/Symfony/Bridge/Twig';
		// le chemin vers les autres templates


		$loader = new \Twig_Loader_Filesystem(array(
			RENZO_ROOT.'/themes/'.static::$themeDir.'/Resources/Templates', // Theme templates
			RENZO_ROOT . '/src/Renzo/CMS/Resources/Templates/forms', // Form extension templates
			$vendorTwigBridgeDir . '/Resources/views/Form' // Form extension templates
		));
		$this->twig = new \Twig_Environment($loader, array(
		    'cache' => $this->getCacheDirectory(),
		));

		$formEngine = new TwigRendererEngine(array(
			$defaultFormTheme,
			'fields.html.twig'
		));
		
		$formEngine->setEnvironment($this->twig);
		// ajoutez à Twig la FormExtension
		$this->twig->addExtension(
		    new FormExtension(new TwigRenderer($formEngine, static::$csrfProvider))
		);

		//RoutingExtension
		$this->twig->addExtension(
		    new RoutingExtension(Kernel::getInstance()->getUrlGenerator())
		);

		/*
		 * ============================================================================
		 * Dump
		 * ============================================================================
		 */
		$dump = new \Twig_SimpleFilter('dump', function ($object) {
		    return var_dump($object);
		});
		$this->twig->addFilter($dump);

		return $this;
	}
	/**
	 * @return \Twig_Environment
	 */
	public function getTwig()
	{
		return $this->twig;
	}

	public function prepareBaseAssignation()
	{
		$this->assignation = array(
			'head' => array(
				'baseUrl' => Kernel::getInstance()->getRequest()->getBaseUrl(),
				'filesUrl' => Kernel::getInstance()->getRequest()->getBaseUrl().'/'.Document::getFilesFolderName(),
				'resourcesUrl' => Kernel::getInstance()->getRequest()->getBaseUrl().'/themes/'.static::$themeDir.'/static/'
			),
			'session' => array(
				'messages' => Kernel::getInstance()->getRequest()->getSession()->getFlashBag()->all(),
				'id' => Kernel::getInstance()->getRequest()->getSession()->getId()
			)
		);

		if (static::getSecurityContext() !== null && 
			static::getSecurityContext()->getToken() !== null ) {
			
			$this->assignation['session']['user'] = static::getSecurityContext()->getToken()->getUser();
		}

		return $this;
	}

	/**
	 * Return a Response with default backend 404 error page
	 * 
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function throw404()
	{
		return new Response(
		    $this->getTwig()->render('404.html.twig', $this->assignation),
		    Response::HTTP_NOT_FOUND,
		    array('content-type' => 'text/html')
		);
	}

	/**
	 * Setup current theme class into database
	 * @return boolean
	 */
	public static function setup()
	{
		$className = get_called_class();
		$theme = Kernel::getInstance()->em()
			->getRepository('RZ\Renzo\Core\Entities\Theme')
			->findOneBy(array('className'=>$className));

		if ($theme === null) {
			$theme = new Theme();
			$theme->setClassName($className);
			$theme->setBackendTheme(static::isBackendTheme());
			$theme->setAvailable(true);

			Kernel::getInstance()->em()->persist($theme);
			Kernel::getInstance()->em()->flush();

			return true;
		}
		return false;
	}

	/**
	 * Enable theme
	 * @return boolean
	 */
	public static function enable()
	{
		$className = get_called_class();
		$theme = Kernel::getInstance()->em()
			->getRepository('RZ\Renzo\Core\Entities\Theme')
			->findOneBy(array('className'=>$className));

		if ($theme !== null) {
			$theme->setAvailable(true);
			Kernel::getInstance()->em()->flush();
			return true;
		}
		return false;
	}
	/**
	 * Enable theme
	 * @return boolean
	 */
	public static function disable()
	{
		$className = get_called_class();
		$theme = Kernel::getInstance()->em()
			->getRepository('RZ\Renzo\Core\Entities\Theme')
			->findOneBy(array('className'=>$className));

		if ($theme !== null) {
			$theme->setAvailable(false);
			Kernel::getInstance()->em()->flush();
			return true;
		}
		return false;
	}

	/**
	 * Register current AppController security scheme in Kernel firewall map
	 * 
	 * @param FirewallMap $firewallMap
	 * @param HttpKernelInterface $httpKernel
	 * @param HttpUtils $httpUtils
	 * 
	 * @see BackendController::appendToFirewallMap
	 */
	public static function appendToFirewallMap( FirewallMap $firewallMap, HttpKernelInterface $httpKernel, HttpUtils $httpUtils, EventDispatcher $dispatcher = null )
	{
		// Implements this method if your app controller need a security context
	}

	/**
	 * @var Symfony\Component\Security\Core\SecurityContext
	 */
	public static $securityContext = null;
	/**
	 * @return Symfony\Component\Security\Core\SecurityContext
	 */
	public static function getSecurityContext()
	{
		return static::$securityContext;
	}
}