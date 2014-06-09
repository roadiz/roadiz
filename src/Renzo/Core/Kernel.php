<?php 

namespace RZ\Renzo\Core;

use Acme\DemoBundle\Command\GreetCommand;
use Symfony\Component\Console\Application;

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
		return $this;
	}
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
		$application->run();
	}

	public function runApp()
	{
		# code...
	}
}