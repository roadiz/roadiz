<?php 
/**
 * Copyright REZO ZERO 2014
 * 
 * 
 * 
 *
 * @file TagsController.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Controllers;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Tag;
use RZ\Renzo\Core\Entities\TagTranslation;
use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Core\Entities\NodeTypeField;
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
* 
*/
class TagsController extends RozierApp
{
	/**
	 * List every tags
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function indexAction( Request $request )
	{
		$tags = Kernel::getInstance()->em()
			->getRepository('RZ\Renzo\Core\Entities\Tag')
			->findAllWithDefaultTranslation();
		$this->assignation['tags'] = $tags;

		return new Response(
			$this->getTwig()->render('tags/list.html.twig', $this->assignation),
			Response::HTTP_OK,
			array('content-type' => 'text/html')
		);
	}

	/**
	 * Return an edition form for requested tag
	 * @param  integer $tag_id        [description]
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function editAction( Request $request, $tag_id )
	{
		$tag = Kernel::getInstance()->em()
			->getRepository('RZ\Renzo\Core\Entities\Tag')
			->findWithDefaultTranslation((int)$tag_id);

		if ($tag !== null) {
			$this->assignation['tag'] = $tag;
			
			$form = $this->buildEditForm( $tag );

			$form->handleRequest();

			if ($form->isValid()) {
		 		$this->editTag($form->getData(), $tag);

		 		$msg = $this->getTranslator()->trans('tag.updated', array('%name%'=>$tag->getDefaultTranslatedTag()->getName()));
		 		$request->getSession()->getFlashBag()->add('confirm', $msg);
	 			$this->getLogger()->info($msg);
		 		/*
		 		 * Force redirect to avoid resending form when refreshing page
		 		 */
		 		$response = new RedirectResponse(
					Kernel::getInstance()->getUrlGenerator()->generate(
						'tagsEditPage',
						array('tag_id' => $tag->getId())
					)
				);
				$response->prepare($request);

				return $response->send();
			}

			$this->assignation['form'] = $form->createView();

			return new Response(
				$this->getTwig()->render('tags/edit.html.twig', $this->assignation),
				Response::HTTP_OK,
				array('content-type' => 'text/html')
			);
		}
		else {
			return $this->throw404();
		}
	}

	/**
	 * Return an creation form for requested tag
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function addAction( Request $request )
	{
		$tag = new Tag();

		$translation = Kernel::getInstance()->em()
				->getRepository('RZ\Renzo\Core\Entities\Translation')
				->findOneBy(array('defaultTranslation'=>true));

		if ($tag !== null && 
			$translation !== null) {

			$this->assignation['tag'] = $tag;
			$form = $this->buildAddForm($tag );

			$form->handleRequest();

			if ($form->isValid()) {
		 		$this->addTag($form->getData(), $tag, $translation);

		 		$msg = $this->getTranslator()->trans('tag.created', array('%name%'=>$tag->getDefaultTranslatedTag()->getName()));
		 		$request->getSession()->getFlashBag()->add('confirm', $msg);
	 			$this->getLogger()->info($msg);
		 		/*
		 		 * Force redirect to avoid resending form when refreshing page
		 		 */
		 		$response = new RedirectResponse(
					Kernel::getInstance()->getUrlGenerator()->generate('tagsHomePage')
				);
				$response->prepare($request);

				return $response->send();
			}

			$this->assignation['form'] = $form->createView();

			return new Response(
				$this->getTwig()->render('tags/add.html.twig', $this->assignation),
				Response::HTTP_OK,
				array('content-type' => 'text/html')
			);
		}
		else {
			return $this->throw404();
		}
	}

	/**
	 * Return an deletion form for requested tag
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function deleteAction( Request $request, $tag_id )
	{
		$tag = Kernel::getInstance()->em()
			->find('RZ\Renzo\Core\Entities\Tag', (int)$tag_id);

		if ($tag !== null) {
			$this->assignation['tag'] = $tag;
			
			$form = $this->buildDeleteForm( $tag );
			$form->handleRequest();

			if ($form->isValid() && 
				$form->getData()['tag_id'] == $tag->getId() ) {

		 		$this->deleteTag($form->getData(), $tag);
		 		$msg = $this->getTranslator()->trans('tag.deleted', array('%name%'=>$tag->getDefaultTranslatedTag()->getName()));
		 		$request->getSession()->getFlashBag()->add('confirm', $msg);
	 			$this->getLogger()->info($msg);

		 		/*
		 		 * Force redirect to avoid resending form when refreshing page
		 		 */
		 		$response = new RedirectResponse(
					Kernel::getInstance()->getUrlGenerator()->generate('tagsHomePage')
				);
				$response->prepare($request);

				return $response->send();
			}

			$this->assignation['form'] = $form->createView();

			return new Response(
				$this->getTwig()->render('tags/delete.html.twig', $this->assignation),
				Response::HTTP_OK,
				array('content-type' => 'text/html')
			);
		}
		else {
			return $this->throw404();
		}
	}

	private function editTag( $data, Tag $tag )
	{
		foreach ($data as $key => $value) {
			$setter = 'set'.ucwords($key);
			$tag->$setter( $value );
		}

		Kernel::getInstance()->em()->flush();
	}

	private function addTag( $data, Tag $tag, Translation $translation )
	{
		$translatedTag = new TagTranslation( $tag, $translation );

		foreach ($data as $key => $value) {

			$setter = 'set'.ucwords($key);

			if ($key == 'name' || $key == 'description') {
				$translatedTag->$setter( $value );
			}
			else {
				$tag->$setter( $value );
			}
		}
		$tag->getTranslatedTags()->add($translatedTag); 

		Kernel::getInstance()->em()->persist($translatedTag);
		Kernel::getInstance()->em()->persist($tag);
		Kernel::getInstance()->em()->flush();
	}

	private function deleteTag( $data, Tag $tag )
	{
		Kernel::getInstance()->em()->remove($tag);
		Kernel::getInstance()->em()->flush();
	}


	private function buildAddForm( Tag $tag )
	{
		$defaults = array(
			'visible' => $tag->isVisible()
		);

		$builder = $this->getFormFactory()
					->createBuilder('form', $defaults)
					->add('name', 'text', array(
						'constraints' => array(
							new NotBlank()
						)
					))
					->add('description', new \RZ\Renzo\CMS\Forms\MarkdownType() , array('required' => false))
					->add('visible', 'checkbox', array('required' => false))
		;
		return $builder->getForm();
	}

	/**
	 * 
	 * @param  Tag   $tag 
	 * @return Symfony\Component\Form\Forms
	 */
	private function buildEditForm( Tag $tag )
	{
		$translation = $tag->getDefaultTranslatedTag();

		$defaults = array(
			'visible' => $tag->isVisible(),
			'name' => $translation->getName(),
			'description' => $translation->getDescription(),
		);

		$builder = $this->getFormFactory()
					->createBuilder('form', $defaults)
					->add('name', 'text', array(
						'constraints' => array(
							new NotBlank()
						)
					))
					->add('description', new \RZ\Renzo\CMS\Forms\MarkdownType() , array('required' => false))
					->add('visible', 'checkbox', array('required' => false))
		;

		return $builder->getForm();
	}

	/**
	 * 
	 * @param  Tag   $tag 
	 * @return Symfony\Component\Form\Forms
	 */
	private function buildDeleteForm( Tag $tag )
	{
		$builder = $this->getFormFactory()
			->createBuilder('form')
			->add('tag_id', 'hidden', array(
				'data' => $tag->getId(),
				'constraints' => array(
					new NotBlank()
				)
			))
		;

		return $builder->getForm();
	}


	public static function getTags()
	{
		return Kernel::getInstance()->em()
			->getRepository('RZ\Renzo\Core\Entities\Tag')
			->findAll();
	}
}