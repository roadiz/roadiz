<?php 

namespace Themes\Rozier\Controllers;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Document;
use RZ\Renzo\Core\Entities\Translation;

use Themes\Rozier\RozierApp;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

class LoginController extends RozierApp {

	public function indexAction( Request $request )
	{	
		$form = $this->buildLoginForm();

		$this->assignation['form'] = $form->createView();

		return new Response(
			$this->getTwig()->render('login/login.html.twig', $this->assignation),
			Response::HTTP_OK,
			array('content-type' => 'text/html')
		);
	}

	public function checkAction( Request $request )
	{	
		if (!static::getSecurityContext()->isGranted('ROLE_ADMIN')) {
		    throw new AccessDeniedException();
		}

		return new Response(
			$this->getTwig()->render('login/check.html.twig', $this->assignation),
			Response::HTTP_OK,
			array('content-type' => 'text/html')
		);
	}



	/**
	 * 
	 * @param  Document   $document 
	 * @return Symfony\Component\Form\Forms
	 */
	private function buildLoginForm(  )
	{
		$defaults = array();

		$builder = $this->getFormFactory()
					->createNamedBuilder(null, 'form', $defaults, array())
					->add('_username', 'text', array('constraints' => array(
						new NotBlank()
					)))
					->add('_password', 'password', array('constraints' => array(
						new NotBlank()
					)))
		;

		return $builder->getForm();
	}
}