<?php 
namespace Themes\Rozier\Controllers;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Theme;
use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\CMS\Controllers\FrontendController;
use Themes\Rozier\RozierApp;

use RZ\Renzo\Core\Exceptions\EntityAlreadyExistsException;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

/**
 * Redirection controller use to update database schema 
 * 
 */
class ThemesController extends RozierApp {


	public function indexAction( Request $request )
	{

		$this->assignation['themes'] = Kernel::getInstance()->em()
				->getRepository('RZ\Renzo\Core\Entities\Theme')
				->findAll();

		return new Response(
			$this->getTwig()->render('themes/list.html.twig', $this->assignation),
			Response::HTTP_OK,
			array('content-type' => 'text/html')
		);
	}
}