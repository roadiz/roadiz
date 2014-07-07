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
namespace Themes\Rozier;

use RZ\Renzo\CMS\Controllers\BackendController;

use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Extension\Csrf\CsrfExtension;
use Symfony\Component\Form\Extension\Csrf\CsrfProvider\SessionCsrfProvider;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Validator\Validation;

use Symfony\Component\HttpFoundation\Response;


class RozierApp extends BackendController {
	
	protected static $themeName =      'Rozier administration theme';
	protected static $themeAuthor =    'Ambroise Maupate, Julien Blanchet';
	protected static $themeCopyright = 'REZO ZERO';
	protected static $themeDir =       'Rozier';

	protected $formFactory = null;

	/**
	 * 
	 */
	protected function getFormFactory()
	{
		if ($this->formFactory === null) {

			// créez le validator - les détails varieront
			$validator = Validation::createValidator();

			$this->formFactory = Forms::createFormFactoryBuilder()
			    ->addExtension(new CsrfExtension($this->csrfProvider))
			    ->addExtension(new ValidatorExtension($validator))
			    ->getFormFactory();
		}

		return $this->formFactory;
	}


	public function indexAction()
	{
		return new Response(
			$this->getTwig()->render('index.html.twig', $this->assignation),
			Response::HTTP_OK,
			array('content-type' => 'text/html')
		);
	}


	public function slirAction($queryString, $filename)
	{
		define('SLIR_CONFIG_CLASSNAME','\RZ\Renzo\Core\Utils\SLIRConfig');
		
		$slir = new \SLIR\SLIR();
		$slir->processRequestFromURL();
	}
}