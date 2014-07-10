<?php 
/**
 * Copyright REZO ZERO 2014
 * 
 * 
 * 
 *
 * @file TranslationsController.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Controllers;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Core\Entities\NodeTypeField;
use Themes\Rozier\RozierApp;

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
class TranslationsController extends RozierApp
{
	/**
	 * List every translations
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function indexAction( Request $request )
	{
		$translations = Kernel::getInstance()->em()
			->getRepository('RZ\Renzo\Core\Entities\Translation')
			->findAll();

		$this->assignation['translations'] = array();

		foreach ($translations as $translation) {

			// Make default forms
			$form = $this->buildMakeDefaultForm( $translation );
			$form->handleRequest();
			if ($form->isValid() && 
				$form->getData()['translation_id'] == $translation->getId()) {
				
		 		$translation->getHandler()->makeDefault();

		 		/*
		 		 * Force redirect to avoid resending form when refreshing page
		 		 */
		 		$response = new RedirectResponse(
					Kernel::getInstance()->getUrlGenerator()->generate(
						'translationsHomePage'
					)
				);
				$response->prepare($request);

				return $response->send();
		 	}

		 	$this->assignation['translations'][] = array(
		 		'translation' => $translation,
		 		'defaultForm' => $form->createView()
		 	);
		}

		return new Response(
			$this->getTwig()->render('translations/list.html.twig', $this->assignation),
			Response::HTTP_OK,
			array('content-type' => 'text/html')
		);
	}

	/**
	 * Return an edition form for requested translation
	 * @param  integer $translation_id        [description]
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function editAction( Request $request, $translation_id )
	{
		$translation = Kernel::getInstance()->em()
			->find('RZ\Renzo\Core\Entities\Translation', (int)$translation_id);

		if ($translation !== null) {
			$this->assignation['translation'] = $translation;
			
			$form = $this->buildEditForm( $translation );
			$form->handleRequest();

			if ($form->isValid()) {
		 		$this->editTranslation($form->getData(), $translation);

		 		/*
		 		 * Force redirect to avoid resending form when refreshing page
		 		 */
		 		$response = new RedirectResponse(
					Kernel::getInstance()->getUrlGenerator()->generate(
						'translationsEditPage',
						array('translation_id' => $translation->getId())
					)
				);
				$response->prepare($request);

				return $response->send();
			}

			$this->assignation['form'] = $form->createView();

			return new Response(
				$this->getTwig()->render('translations/edit.html.twig', $this->assignation),
				Response::HTTP_OK,
				array('content-type' => 'text/html')
			);
		}
		else {
			return $this->throw404();
		}
	}

	/**
	 * Return an creation form for requested translation
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function addAction( Request $request )
	{
		$translation = new Translation();

		if ($translation !== null) {
			$this->assignation['translation'] = $translation;
			
			$form = $this->buildEditForm( $translation );

			$form->handleRequest();

			if ($form->isValid()) {
		 		$this->addTranslation($form->getData(), $translation);

		 		/*
		 		 * Force redirect to avoid resending form when refreshing page
		 		 */
		 		$response = new RedirectResponse(
					Kernel::getInstance()->getUrlGenerator()->generate('translationsHomePage')
				);
				$response->prepare($request);

				return $response->send();
			}

			$this->assignation['form'] = $form->createView();

			return new Response(
				$this->getTwig()->render('translations/add.html.twig', $this->assignation),
				Response::HTTP_OK,
				array('content-type' => 'text/html')
			);
		}
		else {
			return $this->throw404();
		}
	}

	/**
	 * Return an deletion form for requested translation
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function deleteAction( Request $request, $translation_id )
	{
		$translation = Kernel::getInstance()->em()
			->find('RZ\Renzo\Core\Entities\Translation', (int)$translation_id);

		if ($translation !== null) {
			$this->assignation['translation'] = $translation;
			
			$form = $this->buildDeleteForm( $translation );

			$form->handleRequest();

			if ($form->isValid() && 
				$form->getData()['translation_id'] == $translation->getId() ) {

		 		$this->deleteTranslation($form->getData(), $translation);

		 		/*
		 		 * Force redirect to avoid resending form when refreshing page
		 		 */
		 		$response = new RedirectResponse(
					Kernel::getInstance()->getUrlGenerator()->generate('translationsHomePage')
				);
				$response->prepare($request);

				return $response->send();
			}

			$this->assignation['form'] = $form->createView();

			return new Response(
				$this->getTwig()->render('translations/delete.html.twig', $this->assignation),
				Response::HTTP_OK,
				array('content-type' => 'text/html')
			);
		}
		else {
			return $this->throw404();
		}
	}

	private function editTranslation( $data, Translation $translation)
	{
		foreach ($data as $key => $value) {
			$setter = 'set'.ucwords($key);
			$translation->$setter( $value );
		}

		Kernel::getInstance()->em()->flush();

		$this->getSession()->getFlashBag()->add('confirm', 'Translation “'.$translation->getName().'” has been updated');
	}

	private function addTranslation( $data, Translation $translation)
	{
		foreach ($data as $key => $value) {
			$setter = 'set'.ucwords($key);
			$translation->$setter( $value );
		}
		Kernel::getInstance()->em()->persist($translation);
		Kernel::getInstance()->em()->flush();

		$this->getSession()->getFlashBag()->add('confirm', 'Translation “'.$translation->getName().'” has been created');
	}

	private function deleteTranslation( $data, Translation $translation)
	{
		if ($data['translation_id'] == $translation->getId()) {

			if ($translation->isDefaultTranslation() === false) {
				Kernel::getInstance()->em()->remove($translation);
				Kernel::getInstance()->em()->flush();

				$this->getSession()->getFlashBag()->add('confirm', 'Translation “'.$translation->getName().'” has been deleted');
			}
			else {
				$this->getSession()->getFlashBag()->add('error', 'You cannot delete default translation.');
			}
		}
	}


	/**
	 * 
	 * @param  Translation   $translation 
	 * @return Symfony\Component\Form\Forms
	 */
	private function buildEditForm( Translation $translation )
	{
		$defaults = array(
			'name' =>           $translation->getName(),
			'locale' =>    		$translation->getLocale(),
			'available' =>      $translation->isAvailable(),
		);
		$builder = $this->getFormFactory()
					->createBuilder('form', $defaults)
					->add('name', 'text', array(
						'constraints' => array(
							new NotBlank()
						)
					))
					->add('locale', 'choice', array(
						'required' => true,
						'choices' => Translation::$availableLocales
					))
					->add('available', 'checkbox', array('required' => false))
		;

		return $builder->getForm();
	}

	/**
	 * 
	 * @param  Translation   $translation 
	 * @return Symfony\Component\Form\Forms
	 */
	private function buildDeleteForm( Translation $translation )
	{
		$builder = $this->getFormFactory()
			->createBuilder('form')
			->add('translation_id', 'hidden', array(
				'data' => $translation->getId(),
				'constraints' => array(
					new NotBlank()
				)
			))
		;

		return $builder->getForm();
	}

	/**
	 * 
	 * @param  Translation $translation
	 * @return Symfony\Component\Form\Forms
	 */
	private function buildMakeDefaultForm(Translation $translation)
	{
		$builder = $this->getFormFactory()
			->createBuilder('form')
			->add('translation_id', 'hidden', array(
				'data' => $translation->getId(),
				'constraints' => array(
					new NotBlank()
				)
			))
		;

		return $builder->getForm();
	}
}