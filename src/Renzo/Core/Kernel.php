<?php 

namespace RZ\Renzo\Core;

use RZ\Renzo\Inheritance\Doctrine\DataInheritanceEvent;

use Acme\DemoBundle\Command\GreetCommand;
use Symfony\Component\Console\Application;
use Doctrine\ORM\Events;
use Doctrine\ORM\EntityManager;

/**
* 
*/
class Kernel {

	private static $instance = null;

	private $em = null;


	private final function __construct()
	{
		
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
		# code...
	}

	public function updateDatabaseSchema()
	{	
		$tool = new \Doctrine\ORM\Tools\SchemaTool( $this->em );
		$tool->updateSchema();

		/*$application = new Application();
		$application->setAutoExit(false);
		//Create de Schema 
		$options = array('command' => 'orm:schema-tool:update',"--force" => true);
		$application->run(new \Symfony\Component\Console\Input\ArrayInput($options));*/
	}
}