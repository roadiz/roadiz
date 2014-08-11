<?php 
namespace Themes\Rozier\Controllers;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Theme;
use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\CMS\Controllers\FrontendController;
use RZ\Renzo\Core\ListManagers\EntityListManager;
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

	const ITEM_PER_PAGE = 5;

	/**
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function indexAction(Request $request) {
		$this->assignation['themes'] = Kernel::getInstance()->em()
				->getRepository('RZ\Renzo\Core\Entities\Theme')
				->findAll();

		$listManager = new EntityListManager( 
			$request, 
			Kernel::getInstance()->em(), 
			'RZ\Renzo\Core\Entities\Theme'
		);
		$listManager->handle();

		$this->assignation['filters'] = $listManager->getAssignation();


		return new Response(
			$this->getTwig()->render('themes/list.html.twig', $this->assignation),
			Response::HTTP_OK,
			array('content-type' => 'text/html')
		);
	}
	
	/**
	 * Returns an edition form for the requested theme
	 * @param  Symfony\Component\HttpFoundation\Request  $request 
	 * @param  integer  $theme_id
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function editAction(Request $request, $theme_id) {
		$theme = Kernel::getInstance()->em()
			->find('RZ\Renzo\Core\Entities\Theme', (int)$theme_id);

		if ($theme !== null) {

			$form = $this->buildEditForm($theme);
			$form->handleRequest();

			if ($form->isValid() && 
				$form->getData()['theme_id'] == $theme->getId()) {

				try {
			 		$this->editTheme($form->getData(), $theme);
			 		$msg = $this->getTranslator()->trans('theme.updated', array('%classname%'=>$theme->getClassName()));
					$request->getSession()->getFlashBag()->add('confirm', $msg);
		 			$this->getLogger()->info($msg);
		 			
				}
				catch (EntityAlreadyExistsException $e){
					$request->getSession()->getFlashBag()->add('error', $e->getMessage());
		 			$this->getLogger()->warning($e->getMessage());
				}
				catch (\RuntimeException $e){
					$request->getSession()->getFlashBag()->add('error', $e->getMessage());
		 			$this->getLogger()->warning($e->getMessage());
				}

		 		/*
		 		 * Force redirect to avoid resending form when refreshing page
		 		 */
		 		$response = new RedirectResponse(
					Kernel::getInstance()->getUrlGenerator()->generate('themesHomePage')
				);
				$response->prepare($request);

				return $response->send();
			}

			$this->assignation['form'] = $form->createView();

			return new Response(
				$this->getTwig()->render('themes/edit.html.twig', $this->assignation),
				Response::HTTP_OK,
				array('content-type' => 'text/html')
			);
		}
		else {
			return $this->throw404();
		}
	}

	/**
	 * Return a creation form for requested theme.
	 * @param Symfony\Component\HttpFoundation\Request  $request
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function addAction(Request $request) {
		$theme = new Theme();

		if ($theme !== null) {
			$this->assignation['theme'] = $theme;

			$form = $this->buildAddForm($theme);
			$form->handleRequest();
			if ($form->isValid()) {
				try {
			 		$this->addTheme($form->getData(), $theme);
			 		$msg = $this->getTranslator()->trans('theme.created', array('%classname%'=>$theme->getClassName()));
					$request->getSession()->getFlashBag()->add('confirm', $msg);
		 			$this->getLogger()->info($msg);
		 			
				}
				catch (EntityAlreadyExistsException $e){
					$request->getSession()->getFlashBag()->add('error', $e->getMessage());
		 			$this->getLogger()->warning($e->getMessage());
				}

		 		/*
		 		 * Force redirect to avoid resending form when refreshing page
		 		 */
		 		$response = new RedirectResponse(
					Kernel::getInstance()->getUrlGenerator()->generate('themesHomePage')
				);
				$response->prepare($request);

				return $response->send();
			}

			$this->assignation['form'] = $form->createView();

			return new Response(
				$this->getTwig()->render('themes/add.html.twig', $this->assignation),
				Response::HTTP_OK,
				array('content-type' => 'text/html')
			);
		}
	}

	/**
	 * Return a deletion form for requested theme.
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @param  integer  $theme_id
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function deleteAction(Request $request, $theme_id) {
		$theme = Kernel::getInstance()->em()
					->find('RZ\Renzo\Core\Entities\Theme', (int)$theme_id);
		
		if ($theme !== null) {
			$form = $this->buildDeleteForm($theme);
			$form->handleRequest();

			if ($form->isValid() && 
				$form->getData()['theme_id'] == $theme->getId()) {

				try {
			 		$this->deleteTheme($form->getData(), $theme);
			 		$msg = $this->getTranslator()->trans('theme.deleted', array('%name%'=>$theme->getClassName()));
					$request->getSession()->getFlashBag()->add('confirm', $msg);
		 			$this->getLogger()->info($msg);
		 			
				}
				catch(EntityRequiredException $e){
					$request->getSession()->getFlashBag()->add('error', $e->getMessage());
		 			$this->getLogger()->warning($e->getMessage());
				}
				catch(\RuntimeException $e){
					$request->getSession()->getFlashBag()->add('error', $e->getMessage());
		 			$this->getLogger()->warning($e->getMessage());
				}

		 		/*
		 		 * Force redirect to avoid resending form when refreshing page
		 		 */
		 		$response = new RedirectResponse(
					Kernel::getInstance()->getUrlGenerator()->generate('themesHomePage')
				);
				$response->prepare($request);

				return $response->send();
			}

			$this->assignation['form'] = $form->createView();

			return new Response(
				$this->getTwig()->render('themes/delete.html.twig', $this->assignation),
				Response::HTTP_OK,
				array('content-type' => 'text/html')
			);
		}
		else {
			return $this->throw404();
		}
	}

	/**
	 * Build add theme form with classname constraint.
	 * @param  Z\Renzo\Core\Entities\Theme  $theme 
	 * @return Symfony\Component\Form\Forms
	 */
	protected function buildAddForm(Theme $theme) {
		$defaults = array(
			'available' =>  $theme->isAvailable(),
			'className' =>  $theme->getClassName(),
			'hostName' =>   $theme->getHostname(),
			'backendTheme' =>	$theme->isBackendTheme(),
		);


		$builder = $this->getFormFactory()
			->createBuilder('form', $defaults)
			->add('available', 'checkbox', array('required' => false))
			->add('className', 'text', array('required' => false))
			->add('hostName', 'text', array('required' => false))
			->add('backendTheme', 'checkbox', array('required' => false))
		;

		return $builder->getForm();
	}

	/**
	 * Build delete theme form with classname constraint.
	 * @param RZ\Renzo\Core\Entities\Theme  $theme
	 * @return Symfony\Component\Form\Forms
	 */
	protected function buildDeleteForm(Theme $theme) {
		$builder = $this->getFormFactory()
			->createBuilder('form')
			->add('theme_id', 'hidden', array(
				'data'=>$theme->getId()
			))
		;

		return $builder->getForm();
	}

	/**
	 * Build edit theme form with classname constraint.
	 * @param  RZ\Renzo\Core\Entities\Theme  $theme
	 * @return Symfony\Component\Form\Forms
	 */
	protected function buildEditForm(Theme $theme) {
		$defaults = array(
			'classname'=>$theme->getClassName()
		);

		$builder = $this->getFormFactory()
			->createBuilder('form', $defaults)
			->add('theme_id', 'hidden', array(
				'data'=>$theme->getId()
			))
			->add('classname', 'text', array(
				'data'=>$theme->getClassName()
			))
		;

		return $builder->getForm();
	}

	/**
	 * @param array  $data
	 * @return RZ\Renzo\Core\Entities\Theme
	 */
	private function addTheme(array $data, Theme $theme) {
		foreach ($data as $key => $value) {
			$setter = 'set'.ucwords($key);
			$theme->$setter($value);
		}

		$existing = Kernel::getInstance()->em()
			->getRepository('RZ\Renzo\Core\Entities\Theme')
			->findOneBy(array('className'=>$theme->getClassName()));
		if ($existing !== null) {
			throw new EntityAlreadyExistsException($this->getTranslator()->trans('theme.already_exists', array('%name%'=>$theme->getClassName())), 1);
		}
		
		Kernel::getInstance()->em()->persist($theme);
		Kernel::getInstance()->em()->flush();

		return true;
	}

	/**
	 * @param array  $data
	 * @return RZ\Renzo\Core\Entities\Theme
	 */
	private function editTheme(array $data, Theme $theme) {	
		/*if (isset($data['classname'])) {
			$existing = Kernel::getInstance()->em()
					->getRepository('RZ\Renzo\Core\Entities\Theme')
					->findOneBy(array('classname' => $data['name']));
			if ($existing !== null && 
				$existing->getId() != $theme->getId()) {
				throw new EntityAlreadyExistsException($this->getTranslator()->trans("theme.classname.already.exists"), 1);
			}

			$theme->setName($data['classname']);
			Kernel::getInstance()->em()->flush();

			return $theme;
		}
		else {
			throw new \RuntimeException("Theme classname is not defined", 1);
		}
		return null;*/

		/*foreach ($data as $key => $value) {
			if (isset($data['className'])) {
				throw new EntityAlreadyExistsException($this->getTranslator()->trans('theme.cannot_rename_already_exists', array('%className%'=>$theme->getClassName())), 1);
			}
			$setter = 'set'.ucwords($key);
			$theme->$setter($value);
		}
		
		Kernel::getInstance()->em()->flush();

		return true;*/
	}

	/**
	 * @param  array  $data
	 * @param  RZ\Renzo\Core\Entities\Theme  $theme
	 * @return void
	 */
	protected function deleteTheme(array $data, Theme $theme) {
		Kernel::getInstance()->em()->remove($theme);
		Kernel::getInstance()->em()->flush();
	}

}