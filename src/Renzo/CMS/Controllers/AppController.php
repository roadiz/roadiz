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

	
	public function __construct(){
		$this->initializeTwig()
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

		$loader = new \Twig_Loader_Filesystem(RENZO_ROOT.'/themes/'.static::$themeDir.'/Resources/Templates');
		$this->twig = new \Twig_Environment($loader, array(
		    'cache' => $cacheDir,
		));

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