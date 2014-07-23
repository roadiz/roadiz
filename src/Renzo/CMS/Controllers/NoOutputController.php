<?php 

namespace RZ\Renzo\CMS\Controllers;

use RZ\Renzo\Core\Kernel;

use RZ\Renzo\Core\Exceptions\EntityAlreadyExistsException;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;
/**
* 
*/
class NoOutputController {

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
		$this->logger = new \RZ\Renzo\Core\Log\Logger();
	}
}