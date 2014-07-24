<?php 

namespace RZ\Renzo\Core;

use RZ\Renzo\Inheritance\Doctrine\DataInheritanceEvent;
use RZ\Renzo\Core\Routing\MixedUrlMatcher;
use RZ\Renzo\Core\Bags\SettingsBag;
use RZ\Renzo\Core\Entities\Theme;

use Symfony\Component\Console\Application;
use Doctrine\ORM\Events;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;

use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\KernelEvents;

use Symfony\Component\Routing;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\Matcher\Dumper\PhpMatcherDumper;
use Symfony\Component\Routing\Generator\Dumper\PhpGeneratorDumper;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Stopwatch\Stopwatch;

use Symfony\Component\HttpFoundation\RequestMatcher;

use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\Firewall;
use Symfony\Component\Security\Http\FirewallMap;
use Symfony\Component\Security\Http\Firewall\ExceptionListener;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolver;
/**
* 
*/
class Kernel {

	private static $instance = null;

	private $em =           null;
	private $backendDebug = false;
	private $config =       null;

	protected $httpKernel =          null;
	protected $request =             null;
	protected $requestContext =      null;
	protected $response =            null;
	protected $httpUtils =           null;
	protected $context =             null;
	protected $matcher =             null;
	protected $resolver =            null;
	protected $dispatcher =          null;
	protected $urlMatcher =          null;
	protected $urlGenerator =        null;
	protected $stopwatch =           null;
	protected $securityContext =     null;
	protected $backendClass =        null;
	protected $frontendThemes =      null;
	protected $rootCollection =      null;


	private final function __construct() {

		$this->parseConfig()
			 ->setupEntityManager( $this->getConfig() );

		$this->stopwatch = new Stopwatch();
		$this->stopwatch->start('global');
		$this->rootCollection = new RouteCollection();

		$this->request = Request::createFromGlobals();
		$this->requestContext = new Routing\RequestContext($this->getResolvedBaseUrl());

		$this->dispatcher = new EventDispatcher();
		$this->resolver =   new ControllerResolver();
	}

	/**
	 * 
	 * @return $this
	 */
	public function parseConfig()
	{
		$configFile = RENZO_ROOT.'/conf/config.json';
		if (file_exists($configFile)) {

			$this->setConfig(
				json_decode(file_get_contents($configFile), true)
			);
		}
		else {
			$this->setConfig(null);
		}

		return $this;
	}
	/**
	 * get entities search paths
	 * @return array
	 */
	public function getEntitiesPaths()
	{
		return array(
			"src/Renzo/Core/Entities", 
			"src/Renzo/Core/AbstractEntities", 
			"sources/GeneratedNodeSources"
		);
	}
	/**
	 * Initialize Doctrine entity manager
	 * @param  array $config
	 * @return $this
	 */
	public function setupEntityManager( $config )
	{
		// the connection configuration
		if ($config !== null) {
			$paths = $this->getEntitiesPaths();

			$dbParams = $config["doctrine"];
			$configDB = Setup::createAnnotationMetadataConfiguration($paths, $this->isDebug());

			$configDB->setProxyDir(RENZO_ROOT . '/sources/Proxies');
			$configDB->setProxyNamespace('Proxies');

			$this->setEntityManager(EntityManager::create($dbParams, $configDB));

			if ($this->em()->getConfiguration()->getResultCacheImpl() !== null) {
				$this->em()->getConfiguration()->getResultCacheImpl()->setNamespace($config["appNamespace"]);
			}
			if ($this->em()->getConfiguration()->getHydrationCacheImpl() !== null) {
				$this->em()->getConfiguration()->getHydrationCacheImpl()->setNamespace($config["appNamespace"]);
			}
			if ($this->em()->getConfiguration()->getQueryCacheImpl() !== null) {
				$this->em()->getConfiguration()->getQueryCacheImpl()->setNamespace($config["appNamespace"]);
			}
			if ($this->em()->getConfiguration()->getMetadataCacheImpl()) {
				$this->em()->getConfiguration()->getMetadataCacheImpl()->setNamespace($config["appNamespace"]);
			}
		}

		return $this;
	}

	/**
	 * @return array
	 */
	public function getConfig() {
	    return $this->config;
	}
	/**
	 * @param array $newconfig
	 */
	public function setConfig($config) {
	    $this->config = $config;
	    return $this;
	}

	/**
	 * Get application debug status.
	 * @return boolean
	 */
	public function isDebug() {
		$conf = $this->getConfig();
		return (boolean)$conf['devMode'];
	}
	/**
	 * Get backend application debug status
	 * @return boolean
	 */
	public function isBackendDebug()
	{
		return $this->backendDebug;
	}

	/**
	 * Return unique instance of Kernel
	 * @return Kernel
	 */
	public static function getInstance(){

		if (static::$instance === null) {
			static::$instance = new Kernel();
		}

		return static::$instance;
	}

	/**
	 * @return Symfony\Component\Stopwatch\Stopwatch
	 */
	public function getStopwatch() { return $this->stopwatch; }

	/**
	 * Set kernel Doctrine entity manager and 
	 * prepare custom data inheritance for Node system
	 * 
	 * @param EntityManager $em 
	 */
	public function setEntityManager(EntityManager $em)
	{
		$this->em = $em;

		$evm = $this->em->getEventManager();
 
        /*
         * Create dynamic dicriminator map for our Node system
         */
        $inheritableEntityEvent = new DataInheritanceEvent();
        $evm->addEventListener(Events::loadClassMetadata, $inheritableEntityEvent);

		return $this;
	}
	/**
	 * 
	 * @return Doctrine\ORM\EntityManager
	 */
	public function getEntityManager()
	{
		return $this->em;
	}
	/**
	 * Alias for Kernel::getEntityManager method
	 * @return Doctrine\ORM\EntityManager
	 */
	public function em() { return $this->em; }

	/**
	 * 
	 * @return RZ\Renzo\Core\Kernel $this
	 */
	public function runConsole()
	{
		$this->backendDebug = 	(boolean)SettingsBag::get('backend_debug');
		
		if ($this->getConfig() === null || 
			(isset($this->getConfig()['install']) && 
			 $this->getConfig()['install'] == true)) {

			$this->prepareSetup();
		}
		else {
			$this->prepareUrlHandling();
		}
		

		$application = new Application('Renzo Console Application', '0.1');
		$application->add(new \RZ\Renzo\Console\TranslationsCommand);
		$application->add(new \RZ\Renzo\Console\NodeTypesCommand);
		$application->add(new \RZ\Renzo\Console\NodesCommand);
		$application->add(new \RZ\Renzo\Console\SchemaCommand);
		$application->add(new \RZ\Renzo\Console\ThemesCommand);
		$application->add(new \RZ\Renzo\Console\InstallCommand);
		$application->add(new \RZ\Renzo\Console\UsersCommand);
		
		$application->run();

		$this->stopwatch->stop('global');
		return $this;
	}
	/**
	 * 
	 * Run main HTTP application
	 * 
	 * @return RZ\Renzo\Core\Kernel $this
	 */
	public function runApp()
	{
		$this->debug = 			(boolean)SettingsBag::get('debug');
		$this->backendDebug = 	(boolean)SettingsBag::get('backend_debug');

		$this->httpKernel = new HttpKernel($this->dispatcher, $this->resolver);
		
		if ($this->getConfig() === null || 
			(isset($this->getConfig()['install']) && 
			 $this->getConfig()['install'] == true)) {

			$this->prepareSetup();
		}
		else {
			$this->prepareRequestHandling();
		}

		try{
			/*
			 * ----------------------------
			 * Main Framework handle call
			 * ----------------------------
			 */
			$this->stopwatch->start('requestHandling');
			$this->response = $this->httpKernel->handle( $this->request );

			$this->response->send();
			
			$this->httpKernel->terminate( $this->request, $this->response );
			$this->stopwatch->stop('requestHandling');
		}
		catch(\Symfony\Component\Routing\Exception\ResourceNotFoundException $e){
			echo $e->getMessage().PHP_EOL;
		}
		catch(\LogicException $e){
			echo $e->getMessage().PHP_EOL;
		}
		catch(\PDOException $e){
			echo $e->getMessage().PHP_EOL;
		}

		return $this;
	}
	/**
	 * Run first installation interface
	 * 
	 * @return RZ\Renzo\Core\Kernel $this
	 */
	public function prepareSetup()
	{
		$feCollection = \Themes\Install\InstallApp::getRoutes();
		if ($feCollection !== null) {

			$this->rootCollection->addCollection($feCollection);
			$this->urlMatcher =   new UrlMatcher($this->rootCollection, $this->requestContext);
			$this->urlGenerator = new UrlGenerator($this->rootCollection, $this->requestContext);
			$this->httpUtils =    new HttpUtils($this->urlGenerator, $this->urlMatcher);

			$this->dispatcher->addSubscriber(new RouterListener($this->urlMatcher));
		}

		return $this;
	}

	private function prepareRouteCollection()
	{
		/*
		 * Add Assets controller routes
		 */
		$this->rootCollection->addCollection(\RZ\Renzo\CMS\Controllers\AssetsController::getRoutes());

		/*
		 * Add Backend routes
		 */
		$beClass = $this->backendClass;
		$cmsCollection = $beClass::getRoutes();
		if ($cmsCollection !== null) {
			
			$this->rootCollection->addCollection($cmsCollection, '/rz-admin', array('_scheme' => 'https'));
		}

		/*
		 * Add Frontend routes
		 *
		 * return 'RZ\Renzo\CMS\Controllers\FrontendController';
		 */
		foreach ($this->frontendThemes as $theme) {
			$feClass = $theme->getClassName();
			$feCollection = $feClass::getRoutes();
			if ($feCollection !== null) {

				// set host pattern if defined
				if ($theme->getHostname() != '*' && 
					$theme->getHostname() != '') {

					$feCollection->setHost($theme->getHostname());
				}
				$this->rootCollection->addCollection($feCollection);
			}
		}

		if (!file_exists(RENZO_ROOT.'/sources/Compiled')) {
			mkdir(RENZO_ROOT.'/sources/Compiled', 0755, true);
		}

		/*
		 * Generate custom UrlMatcher
		 */
		$dumper = new PhpMatcherDumper($this->rootCollection);
		$class = $dumper->dump(array(
			'class' => 'GlobalUrlMatcher'
		));
		file_put_contents(RENZO_ROOT.'/sources/Compiled/GlobalUrlMatcher.php', $class);
		
		/*
		 * Generate custom UrlGenerator
		 */
		$dumper = new PhpGeneratorDumper($this->rootCollection);
		$class = $dumper->dump(array(
			'class' => 'GlobalUrlGenerator'
		));
		file_put_contents(RENZO_ROOT.'/sources/Compiled/GlobalUrlGenerator.php', $class);


		return $this;
	}


	/**
	 * Prepare URL generation tools
	 * @return this
	 */
	private function prepareUrlHandling()
	{
		$this->backendClass = $this->getBackendClass();
		$this->frontendThemes = $this->getFrontendThemes();

		if ($this->isDebug() || 
			!file_exists(RENZO_ROOT.'/sources/Compiled/GlobalUrlMatcher.php') || 
			!file_exists(RENZO_ROOT.'/sources/Compiled/GlobalUrlGenerator.php')) {

			$this->prepareRouteCollection();
		}

		//$this->urlMatcher =   new \GlobalUrlMatcher($this->requestContext);
		$this->urlMatcher =   new MixedUrlMatcher($this->requestContext);
		$this->urlGenerator = new \GlobalUrlGenerator($this->requestContext);
		$this->httpUtils =    new HttpUtils($this->urlGenerator, $this->urlMatcher);

		return $this;
	}

	private function prepareTranslation()
	{
        /*
         * set default locale
         */
        $translation = Kernel::getInstance()->em()
        	->getRepository('RZ\Renzo\Core\Entities\Translation')
        	->findOneBy(array(
        		'defaultTranslation'=>true, 
        		'available'=>true
        	));

        if ($translation !== null) {
        	$shortLocale = $translation->getShortLocale();
        	Kernel::getInstance()->getRequest()->setLocale($shortLocale);
        	\Locale::setDefault($shortLocale);
        }
	}

	/**
	 * 
	 * Prepare backend and frontend routes and logic
	 * @return boolean
	 */
	private function prepareRequestHandling()
	{	
		$this->stopwatch->start('prepareTranslation');
		$this->prepareTranslation();
		$this->stopwatch->stop('prepareTranslation');

		$this->stopwatch->start('prepareRouting');
		$this->prepareUrlHandling();
		$this->stopwatch->stop('prepareRouting');

		$this->dispatcher->addSubscriber(new RouterListener($this->urlMatcher));

		/*
		 * Security
		 */
		$this->stopwatch->start('firewall');
		
		$map = new FirewallMap();
		// Register back-end security scheme
		$beClass = $this->backendClass;
		$beClass::appendToFirewallMap( $map, $this->httpKernel, $this->httpUtils, $this->dispatcher );

		// Register front-end security scheme
		foreach ($this->frontendThemes as $theme) {
			$feClass = $theme->getClassName();
			$feClass::appendToFirewallMap( $map, $this->httpKernel, $this->httpUtils, $this->dispatcher );
		}

		$firewall = new Firewall($map, $this->dispatcher);
		$this->dispatcher->addListener(
		    KernelEvents::REQUEST,
		    array($firewall, 'onKernelRequest')
		);

		/*
		 * Register after controller matched listener
		 */
		$this->dispatcher->addListener(
		    KernelEvents::CONTROLLER,
		    array($this, 'onControllerMatched')
		);

		$this->stopwatch->stop('firewall');

		/*
		 * If debug, alter HTML responses to append Debug panel to view
		 */
		if ((boolean)SettingsBag::get('display_debug_panel')) {
			$this->dispatcher->addSubscriber(new \RZ\Renzo\Core\Utils\DebugPanel());
		}
	}

	/**
	 * After a controller has been matched
	 * @param  SymfonyComponentHttpKernelEventFilterControllerEvent $event 
	 * @return void
	 */
	public function onControllerMatched(\Symfony\Component\HttpKernel\Event\FilterControllerEvent $event)
	{
		$this->stopwatch->stop('matchingRoute');
	}

	/**
	 * Get frontend app themes
	 * 
	 * 
	 * @return ArrayCollection of RZ\Renzo\Core\Entities\Theme
	 */
	private function getFrontendThemes()
	{
		$themes = $this->em()
			->getRepository('RZ\Renzo\Core\Entities\Theme')
			->findBy(array('available'=>true, 'backendTheme'=>false));

		if (count($themes) < 1) {

			$defaultTheme = new Theme();
			$defaultTheme->setClassName('RZ\Renzo\CMS\Controllers\FrontendController');
			$defaultTheme->setAvailable(true);

			return array(
				$defaultTheme
			);
		}
		else{
			return $themes; 
		}
	}
	/**
	 * Get backend app controller full-qualified classname
	 * 
	 * Return 'RZ\Renzo\CMS\Controllers\BackendController' if none found in database
	 * 
	 * @return string
	 */
	private function getBackendClass()
	{
		$theme = $this->em()
			->getRepository('RZ\Renzo\Core\Entities\Theme')
			->findOneBy(array('available'=>true, 'backendTheme'=>true));

		if ($theme !== null) {
			return $theme->getClassName();
		}

		return 'RZ\Renzo\CMS\Controllers\BackendController';
	}

	/**
	 * @return Symfony\Component\HttpFoundation\Request
	 */
	public function getRequest()
	{
		return $this->request;
	}

	/**
	 * 
	 * @return Symfony\Component\Routing\Generator\UrlGenerator
	 */
	public function getUrlGenerator()
	{
		return $this->urlGenerator;
	}

	/**
	 * Resolve current front controller URL
	 * 
	 * This method is the base of every URL building methods in RZ-CMS. 
	 * Be careful with handling it.
	 * 
	 * @return string 
	 */
	private function getResolvedBaseUrl()
	{
		if (isset($_SERVER["SERVER_NAME"])) {
			$url = pathinfo($_SERVER['PHP_SELF']);

			// Protocol
			$pageURL = 'http';
			if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
			$pageURL .= "://";
			// Port
			if (isset($_SERVER["SERVER_PORT"]) && $_SERVER["SERVER_PORT"] != "80") {
				$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"];
			} else {
				$pageURL .= $_SERVER["SERVER_NAME"];
			}
			// Non root folder
			if (!empty($url["dirname"]) && $url["dirname"] != '/') {
				$pageURL .= $url["dirname"];
			}

			return $pageURL;
		}
		else {
			return false;
		}
	}

	/**
	 * Execute after kernel response
	 * 
	 * Build debug panel if debug is ON
	 * 
	 * @param  FilterResponseEvent $event
	 * @return void
	 */
	public function onKernelResponse(FilterResponseEvent $event)
	{
	   	if ($this->isDebug()) {

	    	$response = $event->getResponse();

	    	if (strpos($response->getContent(), '</body>') !== false) {
	    		$sw = $this->stopwatch->stop('global');
	    		$debug = $sw->getCategory().' : '.$sw->getDuration().'ms - '.$sw->getMemory()/1000000.0.'Mo';

	    		$content = str_replace('</body>', $debug."</body>", $response->getContent());

	    		$response->setContent($content);
	    	}
	   	}
	}
}