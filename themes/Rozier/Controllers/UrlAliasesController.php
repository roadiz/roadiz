<?php 
/**
 * Copyright REZO ZERO 2014
 * 
 * 
 * 
 *
 * @file NodesController.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */

namespace Themes\Rozier\Controllers;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Node;
use RZ\Renzo\Core\Entities\NodeType;
use RZ\Renzo\Core\Entities\NodeTypeField;
use RZ\Renzo\Core\Entities\UrlAlias;
use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Core\Handlers\NodeHandler;
use RZ\Renzo\Core\Utils\StringHandler;
use Themes\Rozier\RozierApp;

use RZ\Renzo\Core\Exceptions\EntityAlreadyExistsException;
use RZ\Renzo\Core\Exceptions\NoTranslationAvailableException;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use \Symfony\Component\Form\Form;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;



class UrlAliasesController extends RozierApp {

	/**
	 * Return aliases form for requested node.
	 * @param  Symfony\Component\HttpFoundation\Request $request
	 * @param  int  $node_id        
	 * @param  int  $translation_id 
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function editAliasesAction(Request $request, $node_id) {
		$translation = Kernel::getInstance()->em()
				->getRepository('RZ\Renzo\Core\Entities\Translation')
        		->findDefault();
		$node = Kernel::getInstance()->em()
			->find('RZ\Renzo\Core\Entities\Node', (int)$node_id);

		if ($node !== null) {

			$uas = Kernel::getInstance()->em()
							->getRepository('RZ\Renzo\Core\Entities\UrlAlias')
							->findAllFromNode($node->getId());

			$this->assignation['node'] = $node;
			$this->assignation['aliases'] = array();
			$this->assignation['translation'] = $translation;

			/*
			 * each url alias edit form
			 */
			foreach ($uas as $alias) {
				$editForm = $this->buildEditUrlAliasForm($alias);
				$deleteForm = $this->buildDeleteUrlAliasForm($alias);

				// Match edit
				$editForm->handleRequest();
				if ($editForm->isValid()) {

					if ($this->editUrlAlias($editForm->getData(), $alias)) {

						$msg = $this->getTranslator()->trans('url_alias.updated', array('%alias%'=>$alias->getAlias()));
						$request->getSession()->getFlashBag()->add('confirm', $msg);
	 					$this->getLogger()->info($msg);
					}
					else {
						$msg = $this->getTranslator()->trans('url_alias.no_update.already_exists', array('%alias%'=>$alias->getAlias()));
						$request->getSession()->getFlashBag()->add('error', $msg);
	 					$this->getLogger()->warning($msg);
					}

					/*
			 		 * Force redirect to avoid resending form when refreshing page
			 		 */
			 		$response = new RedirectResponse(
						Kernel::getInstance()->getUrlGenerator()->generate(
							'nodesEditAliasesPage',
							array('node_id' => $node->getId())
						)
					);
					$response->prepare($request);

					return $response->send();
				}
				// Match delete
				$deleteForm->handleRequest();
				if ($deleteForm->isValid()) {

					$this->deleteUrlAlias($editForm->getData(), $alias);
					$msg = $this->getTranslator()->trans('url_alias.deleted', array('%alias%'=>$alias->getAlias()));
					$request->getSession()->getFlashBag()->add('confirm', $msg);
	 				$this->getLogger()->info($msg);
					/*
			 		 * Force redirect to avoid resending form when refreshing page
			 		 */
			 		$response = new RedirectResponse(
						Kernel::getInstance()->getUrlGenerator()->generate(
							'nodesEditAliasesPage',
							array('node_id' => $node->getId())
						)
					);
					$response->prepare($request);

					return $response->send();
				}

				$this->assignation['aliases'][] = array(
					'alias'=>$alias,
					'editForm'=>$editForm->createView(),
					'deleteForm'=>$deleteForm->createView()
				);
			}

			/* 
			 * =======================
			 * Main ADD url alias form
			 */
			$form = $this->buildAddUrlAliasForm( $node );
			$form->handleRequest();

			if ($form->isValid()) {

				try {
		 			$ua = $this->addNodeUrlAlias($form->getData(), $node);
		 			$msg = $this->getTranslator()->trans('url_alias.created', array(
		 				'%alias%'=>$ua->getAlias(), 
		 				'%translation%'=>$ua->getNodeSource()->getTranslation()->getName()
		 			));
		 			$request->getSession()->getFlashBag()->add('confirm', $msg);
	 				$this->getLogger()->info($msg);

				}
				catch( EntityAlreadyExistsException $e ){
					$request->getSession()->getFlashBag()->add('error', $e->getMessage());
					$this->getLogger()->warning($e->getMessage());
				}
				catch( NoTranslationAvailableException $e ){
					$request->getSession()->getFlashBag()->add('error', $e->getMessage());
					$this->getLogger()->warning($e->getMessage());
				}
	 			/*
		 		 * Force redirect to avoid resending form when refreshing page
		 		 */
		 		$response = new RedirectResponse(
					Kernel::getInstance()->getUrlGenerator()->generate(
						'nodesEditAliasesPage',
						array('node_id' => $node->getId())
					)
				);
				$response->prepare($request);
				return $response->send();
			}

			$this->assignation['form'] = $form->createView();

			return new Response(
				$this->getTwig()->render('nodes/editAliases.html.twig', $this->assignation),
				Response::HTTP_OK,
				array('content-type' => 'text/html')
			);
		}
		
		return $this->throw404();
	}


	/**
	 * @param array  $data 
	 * @param RZ\Renzo\Core\Entities\Node  $node
	 * @return RZ\Renzo\Core\Entities\UrlAlias
	 */
	private function addNodeUrlAlias($data, Node $node) {
		if ($data['node_id'] == $node->getId()) {

			$translation = Kernel::getInstance()->em()
						->find('RZ\Renzo\Core\Entities\Translation', (int)$data['translation_id']);

			$nodeSource = Kernel::getInstance()->em()
						->getRepository('RZ\Renzo\Core\Entities\NodesSources')
						->findOneBy(array('node'=>$node, 'translation'=>$translation));

			if ($translation !== null && 
				$nodeSource !== null) {

				$testingAlias = StringHandler::slugify($data['alias']);
				if ($this->nodeNameExists($testingAlias) || 
						$this->urlAliasExists($testingAlias)) {

					$msg = $this->getTranslator()->trans('url_alias.no_creation.already_exists', array('%alias%'=>$data['alias']));
					throw new EntityAlreadyExistsException($msg, 1);
				}
				
				try {
					$ua = new UrlAlias( $nodeSource );
					$ua->setAlias($data['alias']);
					Kernel::getInstance()->em()->persist($ua);
					Kernel::getInstance()->em()->flush();
					return $ua;
				}
				catch(\Exception $e){
					$msg = $this->getTranslator()->trans('url_alias.no_creation.already_exists', array('%alias%'=>$testingAlias));
					throw new EntityAlreadyExistsException($msg, 1);
				}
			}
			else{
				$msg = $this->getTranslator()->trans('url_alias.no_translation', array('%translation%'=>$translation->getName()));
				throw new NoTranslationAvailableException($msg, 1);
			}
		}
		return null;
	}

	/**
	 * @param  string  $name
	 * @return bool
	 */
	private function urlAliasExists($name) {
		return (boolean)Kernel::getInstance()->em()
			->getRepository('RZ\Renzo\Core\Entities\UrlAlias')
			->exists( $name );
	}

	private function nodeNameExists($name) {
		return (boolean)Kernel::getInstance()->em()
			->getRepository('RZ\Renzo\Core\Entities\Node')
			->exists( $name );
	}

	/**
	 * @param  array  $data
	 * @param  RZ\Renzo\Core\Entities\UrlAlias  $ua 
	 * @return void
	 */
	private function editUrlAlias( $data, UrlAlias $ua) {
		$testingAlias = StringHandler::slugify($data['alias']);
		if ($testingAlias != $ua->getAlias() && 
				($this->nodeNameExists($testingAlias) || 
				$this->urlAliasExists($testingAlias))) {

			$msg = $this->getTranslator()->trans('url_alias.no_update.already_exists', array('%alias%'=>$data['alias']));
			throw new EntityAlreadyExistsException($msg, 1);
		}

		if ($data['urlalias_id'] == $ua->getId()) {
			
			try {
				$ua->setAlias($data['alias']);
				Kernel::getInstance()->em()->flush();
				return true;
			}
			catch(\Exception $e){
				return false;
			}
		}
	}

	/**
	 * @param  array  $data
	 * @param  RZ\Renzo\Core\Entities\UrlAlias  $ua 
	 * @return void
	 */
	private function deleteUrlAlias($data, UrlAlias $ua) {
		if ($data['urlalias_id'] == $ua->getId()) {
			
			Kernel::getInstance()->em()->remove($ua);
			Kernel::getInstance()->em()->flush();
		}
	}

	/**
	 * @param  RZ\Renzo\Core\Entities\Node  $node 
	 * @return \Symfony\Component\Form\Form
	 */
	private function buildAddUrlAliasForm(Node $node) {
		$defaults = array(
			'node_id' =>  $node->getId()
		);
		$builder = $this->getFormFactory()
			->createBuilder('form', $defaults)
			->add('node_id', 'hidden', array(
				'data' => $node->getId(),
				'constraints' => array(
					new NotBlank()
				)
			))
			->add('alias', 'text' )
			->add('translation_id', new \RZ\Renzo\CMS\Forms\TranslationsType() );

		return $builder->getForm();
	}

	/**
	 * @param  RZ\Renzo\Core\Entities\UrlAlias  $ua
	 * @return \Symfony\Component\Form\Form
	 */
	private function buildEditUrlAliasForm(UrlAlias $ua) {
		$defaults = array(
			'urlalias_id' =>  $ua->getId(),
			'alias' =>  $ua->getAlias()
		);
		$builder = $this->getFormFactory()
					->createBuilder('form', $defaults)
					->add('urlalias_id', 'hidden', array(
						'data' => $ua->getId(),
						'constraints' => array(
							new NotBlank()
						)
					))
					->add('alias', 'text', array(
						'constraints' => array(
							new NotBlank()
						)
					));

		return $builder->getForm();
	}

	/**
	 * @param  RZ\Renzo\Core\Entities\UrlAlias  $ua
	 * @return \Symfony\Component\Form\Form
	 */
	private function buildDeleteUrlAliasForm(UrlAlias $ua) {
		$defaults = array(
			'urlalias_id' =>  $ua->getId()
		);
		$builder = $this->getFormFactory()
					->createBuilder('form', $defaults)
					->add('urlalias_id', 'hidden', array(
						'data' => $ua->getId(),
						'constraints' => array(
							new NotBlank()
						)
					));

		return $builder->getForm();
	}
}