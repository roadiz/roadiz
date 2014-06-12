<?php 

namespace RZ\Renzo\Core;

use RZ\Renzo\Inheritance\Doctrine\DataInheritanceEvent;
use RZ\Renzo\Core\Routing\MixedUrlMatcher;

use Acme\DemoBundle\Command\GreetCommand;
use Symfony\Component\Console\Application;
use Doctrine\ORM\Events;
use Doctrine\ORM\EntityManager;

use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\Routing;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\Loader\YamlFileLoader;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Stopwatch\Stopwatch;

/**
* 
*/
class Kernel {

	private static $instance = null;

	private $em =           null;
	private $debug =        true;
	protected $httpKernel = null;
	protected $request =    null;
	protected $response =   null;
	protected $context =    null;
	protected $matcher =    null;
	protected $resolver =   null;
	protected $dispatcher = null;
	protected $stopwatch =  null;


	private final function __construct() {

		$this->stopwatch = new Stopwatch();
		$this->stopwatch->start('global');

		$this->request = Request::createFromGlobals();
	}

	/**
	 * Get application debug status.
	 * @return boolean
	 */
	public function isDebug() {
		return $this->debug;
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

	public function getStopwatch()
	{
		return $this->stopwatch;
	}

	/**
	 * [setEntityManager description]
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
	 * @return [type] [description]
	 */
	public function runConsole()
	{
		$application = new Application('Renzo Console Application', '0.1');
		$application->add(new \RZ\Renzo\Console\TranslationsCommand);
		$application->add(new \RZ\Renzo\Console\NodeTypesCommand);
		$application->add(new \RZ\Renzo\Console\NodesCommand);
		$application->add(new \RZ\Renzo\Console\SchemaCommand);
		$application->run();

		$this->stopwatch->stop('global');
	}
	/**
	 * 
	 * 
	 * Run main HTTP application
	 */
	public function runApp()
	{
		$this->dispatcher = new EventDispatcher();
		$this->resolver =   new ControllerResolver();
		$this->httpKernel = new HttpKernel($this->dispatcher, $this->resolver);
		
		/*
		 * Main routing
		 */
		$this->handleBackendFrontend();

		try{
			$this->response = $this->httpKernel->handle( $this->request );
			$event = $this->stopwatch->stop('global');
			echo $event->getCategory().' : '.$event->getDuration().'ms - '.$event->getMemory()/1000000.0.'Mo';

			$this->response->send();
			$this->httpKernel->terminate( $this->request, $this->response );
		}
		catch(\Exception $e){
			echo $e->getMessage();
		}
	}

	/**
	 * 
	 * Handle Backend routes and logic
	 * @return boolean
	 */
	private function handleBackendFrontend()
	{
		try{
			$locator = new FileLocator(array(
				RENZO_ROOT.'/src/Renzo/CMS/Resources'
			));
			$loader = new YamlFileLoader($locator);
			$cmsCollection = $loader->load('routes.yml');

			$matcher = new MixedUrlMatcher($cmsCollection, new Routing\RequestContext());
			$this->dispatcher->addSubscriber(new RouterListener($matcher));

			return true;
		}
		catch(Symfony\Component\Routing\Exception\ResourceNotFoundException $e){
			echo $e->getMessage();
		}
		catch(\LogicException $e){
			echo $e->getMessage();
		}
		catch(\Exception $e){
			echo $e->getMessage();
		}

		return false;
	}

	/**
	 * @return Symfony\Component\HttpFoundation\Request
	 */
	public function getRequest()
	{
		return $this->request;
	}
}