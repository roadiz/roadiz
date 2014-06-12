<?php 

namespace RZ\Renzo\Core;

use RZ\Renzo\Inheritance\Doctrine\DataInheritanceEvent;

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

/**
* 
*/
class Kernel {

	private static $instance = null;

	private $em =           null;
	private $debug =        true;
	protected $request =    null;
	protected $response =   null;
	protected $context =    null;
	protected $matcher =    null;
	protected $resolver =   null;
	protected $dispatcher = null;


	public function __construct() {
		$this->request = Request::createFromGlobals();
	}

	/**
	 * Get application debug status.
	 * @return boolean
	 */
	public function isDebug() {
		return $this->debug;
	}

	public static function getInstance(){

		if (static::$instance === null) {
			static::$instance = new Kernel();
		}

		return static::$instance;
	}

	public function setEntityManager(EntityManager $em)
	{
		$this->em = $em;

		$evm = $this->em->getEventManager();
 
        // create and then add our event!
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
	}

	public function runApp()
	{
		/*
		 * CMS
		 */
		try{
			$cmsCollection = $this->getCMSRouteCollection();
			$this->matcher = new UrlMatcher($cmsCollection, new Routing\RequestContext());




			$this->dispatcher = new EventDispatcher();
			$this->dispatcher->addSubscriber(new RouterListener($this->matcher));

			$this->resolver = new ControllerResolver();

			$kernel = new HttpKernel($this->dispatcher, $this->resolver);

			$this->response = $kernel->handle( $this->request );
			$this->response->send();

			$kernel->terminate( $this->request, $this->response );
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
	}

	public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
	{
		parent::handle($request, $type, $catch);

		$this->dispatcher->dispatch('response', new ResponseEvent($response, $request));
		return $response;
	}

	private function getCMSRouteCollection()
	{
		$locator = new FileLocator(array(
			RENZO_ROOT.'/src/Renzo/CMS/Resources'
		));
		$loader = new YamlFileLoader($locator);
		$collection = $loader->load('routes.yml');


		return $collection;
	}

	/**
	 * @return Symfony\Component\HttpFoundation\Request
	 */
	public function getRequest()
	{
		return $this->request;
	}
}