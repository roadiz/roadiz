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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;


use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;

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
class AppController {
	
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
	protected $assignation = array();

	protected $csrfProvider = null;
	protected $session = null;
	protected $translator = null;

	
	public function __construct(){
		$this->initializeSession()
			->initializeTwig()
			->initializeTranslator()
			->prepareBaseAssignation();
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
	 * @param  string $lang Default: 'en'
	 * @return [type]       [description]
	 */
	private function initializeTranslator( $lang = 'en' )
	{
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

	private function initializeSession()
	{
		// créer un objet session depuis le composant HttpFoundation
		$this->session = new Session();
		$this->session->start();

		// générer le secret CSRF depuis quelque part
		$csrfSecret = Kernel::getInstance()->getConfig()["security"]['secret'];
		$this->csrfProvider = new SessionCsrfProvider($this->session, $csrfSecret);


		return $this;
	}

	/**
	 * Create a Twig Environment instance
	 */
	private function initializeTwig()
	{
		$cacheDir = RENZO_ROOT.'/cache/'.static::$themeDir.'/twig_cache';

		if (Kernel::getInstance()->isDebug()) {
			try {
				$fs = new Filesystem();
				$fs->remove(array($cacheDir));
			} catch (IOExceptionInterface $e) {
			    echo "An error occurred while deleting backend twig cache directory: ".$e->getPath();
			}
		}

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
			$vendorTwigBridgeDir . '/Resources/views/Form' // Form extension templates
		));
		$this->twig = new \Twig_Environment($loader, array(
		    'cache' => $cacheDir,
		));

		$formEngine = new TwigRendererEngine(array($defaultFormTheme));
		$formEngine->setEnvironment($this->twig);
		// ajoutez à Twig la FormExtension
		$this->twig->addExtension(
		    new FormExtension(new TwigRenderer($formEngine, $this->csrfProvider))
		);

		//RoutingExtension
		$this->twig->addExtension(
		    new RoutingExtension(Kernel::getInstance()->getUrlGenerator())
		);

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
				'resourcesUrl' => Kernel::getInstance()->getRequest()->getBaseUrl().'/themes/'.static::$themeDir.'/static/'
			),
			'session' => array(
				'id' => $this->session->getId()
			)
		);
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
}