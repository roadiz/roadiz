<?php 

namespace RZ\Renzo\Core\Handlers;

use RZ\Renzo\Core\Entities\Logger;
use Doctrine\ORM\EntityManager;

class LoggerHandler	
{
	private $log;

	function __construct( Logger $log )
	{
		$this->log =  $log;
	}

	/**
	 *
	 * @return RZ\Renzo\Core\Handlers\LoggerHandler
	 */
	public function persistAndFlush()
	{
		Kernel::getInstance()->em()->persist( $this->log );
		Kernel::getInstance()->em()->flush();

		return $this;
	}
}