<?php 

namespace Themes\Rozier\Controllers;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Entities\Font;
use RZ\Renzo\Core\ListManagers\EntityListManager;

use Themes\Rozier\RozierApp;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\NotBlank;

use RZ\Renzo\Core\Exceptions\EntityAlreadyExistsException;
use RZ\Renzo\Core\Exceptions\EntityRequiredException;

/**
* 
*/
class FontsController extends RozierApp {

	const ITEM_PER_PAGE = 5;

	/**
	 * @param  Symfony\Component\HttpFoundation\Request $request
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function indexAction(Request $request) {
		$fonts = Kernel::getInstance()->em()
			->getRepository('RZ\Renzo\Core\Entities\Font')
			->findBy(array(), array('name' => 'ASC'));

		$listManager = new EntityListManager( 
			$request, 
			Kernel::getInstance()->em(), 
			'RZ\Renzo\Core\Entities\Font'
		);
		$listManager->handle();

		$this->assignation['filters'] = $listManager->getAssignation();
		$this->assignation['fonts'] = $fonts;

		return new Response(
			$this->getTwig()->render('fonts/list.html.twig', $this->assignation),
			Response::HTTP_OK,
			array('content-type' => 'text/html')
		);
	}

	/**
	 * Return an creation form for requested font.
	 * @param Symfony\Component\HttpFoundation\Request $request
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function addAction(Request $request) {
		$form = $this->buildAddForm();
		$form->handleRequest();

		if ($form->isValid()) {

			try {
		 		$font = $this->addRole($form->getData());
		 		$msg = $this->getTranslator()->trans('font.created', array('%name%'=>$font->getName()));
				$request->getSession()->getFlashBag()->add('confirm', $msg);
	 			$this->getLogger()->info($msg);
	 			
			}
			catch(EntityAlreadyExistsException $e){
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
				Kernel::getInstance()->getUrlGenerator()->generate('fontsHomePage')
			);
			$response->prepare($request);

			return $response->send();
		}

		$this->assignation['form'] = $form->createView();

		return new Response(
			$this->getTwig()->render('fonts/add.html.twig', $this->assignation),
			Response::HTTP_OK,
			array('content-type' => 'text/html')
		);
	}

	/**
	 * Return a deletion form for requested font.
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @param  int  $font_id
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function deleteAction(Request $request, $font_id) {
		$font = Kernel::getInstance()->em()
					->find('RZ\Renzo\Core\Entities\Font', (int)$font_id);
		if ($font !== null) {

			$form = $this->buildDeleteForm( $font );
			$form->handleRequest();

			if ($form->isValid() && 
				$form->getData()['font_id'] == $font->getId()) {

				try {
			 		$this->deleteFont($form->getData(), $font);
			 		$msg = $this->getTranslator()->trans('font.deleted', array('%name%'=>$font->getName()));
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
					Kernel::getInstance()->getUrlGenerator()->generate('fontsHomePage')
				);
				$response->prepare($request);

				return $response->send();
			}

			$this->assignation['form'] = $form->createView();

			return new Response(
				$this->getTwig()->render('fonts/delete.html.twig', $this->assignation),
				Response::HTTP_OK,
				array('content-type' => 'text/html')
			);
		}
		else {
			return $this->throw404();
		}
	}

	/**
	 * Return an edition form for requested font.
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @param  int  $font_id
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function editAction(Request $request, $font_id) {
		$font = Kernel::getInstance()->em()
					->find('RZ\Renzo\Core\Entities\Font', (int)$font_id);

		if ($font !== null) {

			$form = $this->buildEditForm($font);
			$form->handleRequest();

			if ($form->isValid() && 
				$form->getData()['font_id'] == $font->getId()) {

				try {
			 		$this->editFont($form->getData(), $font);
			 		$msg = $this->getTranslator()->trans('font.updated', array('%name%'=>$font->getName()));
					$request->getSession()->getFlashBag()->add('confirm', $msg);
		 			$this->getLogger()->info($msg);
		 			
				}
				catch(EntityAlreadyExistsException $e){
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
					Kernel::getInstance()->getUrlGenerator()->generate('fontsHomePage')
				);
				$response->prepare($request);

				return $response->send();
			}

			$this->assignation['form'] = $form->createView();

			return new Response(
				$this->getTwig()->render('fonts/edit.html.twig', $this->assignation),
				Response::HTTP_OK,
				array('content-type' => 'text/html')
			);
		}
		else {
			return $this->throw404();
		}
	}

	/**
	 * Build add font form with name constraint.
	 * @return Symfony\Component\Form\Forms
	 */
	protected function buildAddForm() {
		$builder = $this->getFormFactory()
			->createBuilder('form')
			->add('name', 'text', array(
				'label' => $this->getTranslator()->trans('font.name'),
			))
		;

		return $builder->getForm();
	}

	/**
	 * Build delete font form with name constraint.
	 * @param RZ\Renzo\Core\Entities\Font  $font
	 * @return Symfony\Component\Form\Forms
	 */
	protected function buildDeleteForm(Font $font) {
		$builder = $this->getFormFactory()
			->createBuilder('form')
			->add('font_id', 'hidden', array(
				'data'=>$font->getId()
			))
		;

		return $builder->getForm();
	}

	/**
	 * Build edit font form with name constraint.
	 * @param  RZ\Renzo\Core\Entities\Font  $font
	 * @return Symfony\Component\Form\Forms
	 */
	protected function buildEditForm(Font $font) {
		$defaults = array(
			'name'=>$font->getName()
		);
		$builder = $this->getFormFactory()
			->createBuilder('form', $defaults)
			->add('font_id', 'hidden', array(
				'data'=>$font->getId()
			))
			->add('name', 'text', array(
				'data'=>$font->getName()
			))
		;

		return $builder->getForm();
	}

	/**
	 * @param array  $data
	 * @return RZ\Renzo\Core\Entities\Font
	 */
	protected function addFont(array $data) {
		if (isset($data['name'])) {
			$existing = Kernel::getInstance()->em()
					->getRepository('RZ\Renzo\Core\Entities\Font')
					->findOneBy(array('name' => $data['name']));

			if ($existing !== null) {
				throw new EntityAlreadyExistsException($this->getTranslator()->trans("font.name.already_exists"), 1);
			}

			$font = new Font($data['name']);
			Kernel::getInstance()->em()->persist($font);
			Kernel::getInstance()->em()->flush();

			return $font;
		}
		else {
			throw new \RuntimeException("Font name is not defined", 1);
		}
		return null;
	}

	/**
	 * @param array  $data
	 * @return RZ\Renzo\Core\Entities\Font
	 */
	protected function editFont(array $data, Font $font) {	
		if ($font->required()) {
			throw new EntityRequiredException($this->getTranslator()->trans("font.name.cannot_be_updated"), 1);
		}

		if (isset($data['name'])) {
			$existing = Kernel::getInstance()->em()
					->getRepository('RZ\Renzo\Core\Entities\Font')
					->findOneBy(array('name' => $data['name']));
			if ($existing !== null && 
				$existing->getId() != $font->getId()) {
				throw new EntityAlreadyExistsException($this->getTranslator()->trans("font.name.already_exists"), 1);
			}

			$font->setName($data['name']);
			Kernel::getInstance()->em()->flush();

			return $font;
		}
		else {
			throw new \RuntimeException("Font name is not defined", 1);
		}
		return null;
	}

	/**
	 * @param  array  $data
	 * @param  RZ\Renzo\Core\Entities\Font  $font
	 * @return void
	 */
	protected function deleteFont(array $data, Font $font) {
		Kernel::getInstance()->em()->remove($font);
		Kernel::getInstance()->em()->flush();
	}
}