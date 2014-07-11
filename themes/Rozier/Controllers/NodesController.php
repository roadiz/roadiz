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
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;



class NodesController extends RozierApp {
	
	/**
	 * List every nodes
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function indexAction( Request $request )
	{
		$nodes = Kernel::getInstance()->em()
			->getRepository('RZ\Renzo\Core\Entities\Node')
			->findAll();

		$translation = Kernel::getInstance()->em()
				->getRepository('RZ\Renzo\Core\Entities\Translation')
				->findOneBy(array('defaultTranslation'=>true));

		$this->assignation['nodes'] = $nodes;
		$this->assignation['node_types'] = NodeTypesController::getNodeTypes();
		$this->assignation['translation'] = $translation;

		return new Response(
			$this->getTwig()->render('nodes/list.html.twig', $this->assignation),
			Response::HTTP_OK,
			array('content-type' => 'text/html')
		);
	}

	public function noAction()
	{
		return $this->throw404();
	}
	/**
	 * Return an edition form for requested node
	 * 
	 * @param  integer $node_id        [description]
	 * @param  integer $translation_id [description]
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function editAction( Request $request, $node_id, $translation_id = null )
	{
		$node = Kernel::getInstance()->em()
			->find('RZ\Renzo\Core\Entities\Node', (int)$node_id);

		if ($node !== null) {
			$this->assignation['node'] = $node;
			$this->assignation['source'] = $node->getNodeSources()->first();
			
			/*
			 * Handle translation form
			 */
			$translation_form = $this->buildTranslateForm( $node );
			if ($translation_form !== null) {
				$translation_form->handleRequest();

				if ($translation_form->isValid()) {

					try {
				 		$this->translateNode($translation_form->getData(), $node);
				 		$msg = $this->getTranslator()->trans('node.translated', array(
				 			'%name%'=>$node->getNodeName()
				 		));
				 		$request->getSession()->getFlashBag()->add('confirm', $msg);
	 					$this->getLogger()->info($msg);
					}
					catch( EntityAlreadyExistsException $e ){
						$request->getSession()->getFlashBag()->add('error', $e->getMessage());
	 					$this->getLogger()->warning($e->getMessage());
					}
			 		/*
			 		 * Force redirect to avoid resending form when refreshing page
			 		 */
			 		$response = new RedirectResponse(
						Kernel::getInstance()->getUrlGenerator()->generate(
							'nodesEditSourcePage',
							array('node_id' => $node->getId(), 'translation_id'=>$translation_form->getData()['translation_id'])
						)
					);
					$response->prepare($request);
					return $response->send();
				}
				$this->assignation['translation_form'] = $translation_form->createView();
			}

			/*
			 * Handle main form
			 */
			$form = $this->buildEditForm( $node );
			$form->handleRequest();

			if ($form->isValid()) {
				try {
		 			$this->editNode($form->getData(), $node);
		 			$msg = $this->getTranslator()->trans('node.updated', array(
			 			'%name%'=>$node->getNodeName()
			 		));
		 			$request->getSession()->getFlashBag()->add('confirm', $msg);
	 				$this->getLogger()->info($msg);
				}
		 		catch( EntityAlreadyExistsException $e ){
					$request->getSession()->getFlashBag()->add('error', $e->getMessage());
	 				$this->getLogger()->warning($e->getMessage());
				}
		 		/*
		 		 * Force redirect to avoid resending form when refreshing page
		 		 */
		 		$response = new RedirectResponse(
					Kernel::getInstance()->getUrlGenerator()->generate(
						'nodesEditPage',
						array('node_id' => $node->getId())
					)
				);
				$response->prepare($request);

				return $response->send();
			}
			$this->assignation['form'] = $form->createView();

			return new Response(
				$this->getTwig()->render('nodes/edit.html.twig', $this->assignation),
				Response::HTTP_OK,
				array('content-type' => 'text/html')
			);
		}
		

		return $this->throw404();
	}

	/**
	 * Return an edition form for requested node
	 * 
	 * @param  integer $node_id        [description]
	 * @param  integer $translation_id [description]
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function editSourceAction( Request $request, $node_id, $translation_id = null )
	{
		$translation = Kernel::getInstance()->em()
				->getRepository('RZ\Renzo\Core\Entities\Translation')
				->findOneBy(array('defaultTranslation'=>true));

		if ($translation_id !== null) {
			$translation = Kernel::getInstance()->em()
				->find('RZ\Renzo\Core\Entities\Translation', (int)$translation_id);
		}

		if ($translation !== null) {

			$node = Kernel::getInstance()->em()
				->getRepository('RZ\Renzo\Core\Entities\Node')
				->findWithTranslation((int)$node_id, $translation);

			if ($node !== null && 
				$translation !== null) {

				$source = $node->getNodeSources()->first();

				$this->assignation['translation'] = $translation;
				$this->assignation['available_translations'] = $node->getHandler()->getAvailableTranslations();
				$this->assignation['node'] = $node;
				$this->assignation['source'] = $source;
				
				/*
				 * Form
				 */
				$form = $this->buildEditSourceForm( $node, $source );
				$form->handleRequest();

				if ($form->isValid()) {
			 		$this->editNodeSource($form->getData(), $source);

			 		$msg = $this->getTranslator()->trans('node_source.updated', array(
			 			'%node_source%'=>$source->getNode()->getNodeName(), 
			 			'%translation%'=>$source->getTranslation()->getName()
			 		));
			 		$request->getSession()->getFlashBag()->add('confirm',$msg);
	 				$this->getLogger()->info($msg);
			 		/*
			 		 * Force redirect to avoid resending form when refreshing page
			 		 */
			 		$response = new RedirectResponse(
						Kernel::getInstance()->getUrlGenerator()->generate(
							'nodesEditSourcePage',
							array('node_id' => $node->getId(), 'translation_id'=>$translation->getId())
						)
					);
					$response->prepare($request);

					return $response->send();
				}

				$this->assignation['form'] = $form->createView();

				return new Response(
					$this->getTwig()->render('nodes/editSource.html.twig', $this->assignation),
					Response::HTTP_OK,
					array('content-type' => 'text/html')
				);
			}
		}

		return $this->throw404();
	}

	/**
	 * Return tags form for requested node
	 * 
	 * @param  integer $node_id        [description]
	 * @param  integer $translation_id [description]
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function editTagsAction( Request $request, $node_id )
	{
		$translation = Kernel::getInstance()->em()
				->getRepository('RZ\Renzo\Core\Entities\Translation')
				->findOneBy(array('defaultTranslation'=>true));

		if ($translation !== null) {

			$node = Kernel::getInstance()->em()
				->getRepository('RZ\Renzo\Core\Entities\Node')
				->findWithTranslation((int)$node_id, $translation);

			if ($node !== null && 
				$translation !== null) {

				$source = $node->getNodeSources()->first();

				$this->assignation['translation'] = $translation;
				$this->assignation['node'] = 		$node;
				$this->assignation['source'] = 		$source;
				
				$form = $this->buildEditTagsForm( $node );

				$form->handleRequest();

				if ($form->isValid()) {
			 		$tag = $this->addNodeTag($form->getData(), $node);

			 		$msg = $this->getTranslator()->trans('node.tag_linked', array(
			 			'%node%'=>$node->getNodeName(), 
			 			'%tag%'=>$tag->getDefaultTranslatedTag()->getName()
			 		));
			 		$request->getSession()->getFlashBag()->add('confirm', $msg);
	 				$this->getLogger()->info($msg);
			 		/*
			 		 * Force redirect to avoid resending form when refreshing page
			 		 */
			 		$response = new RedirectResponse(
						Kernel::getInstance()->getUrlGenerator()->generate(
							'nodesEditTagsPage',
							array('node_id' => $node->getId())
						)
					);
					$response->prepare($request);

					return $response->send();
				}

				$this->assignation['form'] = $form->createView();

				return new Response(
					$this->getTwig()->render('nodes/editTags.html.twig', $this->assignation),
					Response::HTTP_OK,
					array('content-type' => 'text/html')
				);
			}
		}
		return $this->throw404();
	}

	/**
	 * Return aliases form for requested node
	 * 
	 * @param  integer $node_id        [description]
	 * @param  integer $translation_id [description]
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function editAliasesAction( Request $request, $node_id )
	{
		$node = Kernel::getInstance()->em()
			->find('RZ\Renzo\Core\Entities\Node', (int)$node_id);

		if ($node !== null) {

			$uas = Kernel::getInstance()->em()
							->getRepository('RZ\Renzo\Core\Entities\UrlAlias')
							->findAllFromNode($node->getId());

			$this->assignation['node'] = $node;
			$this->assignation['aliases'] = array();

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
	 * Handle node creation pages
	 * @param [type] $node_type_id   [description]
	 * @param [type] $translation_id [description]
	 */
	public function addAction( Request $request, $node_type_id, $translation_id = null )
	{	
		$type = Kernel::getInstance()->em()
				->find('RZ\Renzo\Core\Entities\NodeType', $node_type_id);

		$translation = Kernel::getInstance()->em()
				->getRepository('RZ\Renzo\Core\Entities\Translation')
				->findOneBy(array('defaultTranslation'=>true));

		if ($translation_id != null) {
			$translation = Kernel::getInstance()->em()
				->find('RZ\Renzo\Core\Entities\Translation', (int)$translation_id);
		}

		if ($type !== null &&
			$translation !== null) {

			$form = $this->getFormFactory()
						->createBuilder()
						->add('nodeName', 'text', array(
							'constraints' => array(
								new NotBlank()
							)
						))
						->getForm();
			$form->handleRequest();

			if ($form->isValid()) {

				try {
					$node = $this->createNode($form->getData(), $type, $translation);

					$msg = $this->getTranslator()->trans('node.created', array('%name%'=>$node->getNodeName()));
					$request->getSession()->getFlashBag()->add('confirm', $msg);
	 				$this->getLogger()->info($msg);

					$response = new RedirectResponse(
						Kernel::getInstance()->getUrlGenerator()->generate(
							'nodesEditPage',
							array('node_id' => $node->getId())
						)
					);
					$response->prepare($request);
					return $response->send();
				}
				catch(EntityAlreadyExistsException $e) {

					$request->getSession()->getFlashBag()->add('error', $e->getMessage());
	 				$this->getLogger()->warning($e->getMessage());

					$response = new RedirectResponse(
						Kernel::getInstance()->getUrlGenerator()->generate(
							'nodesAddPage',
							array('node_type_id' => $node_type_id, 'translation_id' => $translation_id)
						)
					);
					$response->prepare($request);
					return $response->send();
				}
			}

			$this->assignation['translation'] = $translation;
			$this->assignation['form'] = $form->createView();
			$this->assignation['type'] = $type;

			return new Response(
				$this->getTwig()->render('nodes/add.html.twig', $this->assignation),
				Response::HTTP_OK,
				array('content-type' => 'text/html')
			);
		}else {
			return $this->throw404();
		}
	}

	/**
	 * Return an deletion form for requested node
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function deleteAction( Request $request, $node_id )
	{
		$node = Kernel::getInstance()->em()
			->find('RZ\Renzo\Core\Entities\Node', (int)$node_id);

		if ($node !== null) {
			$this->assignation['node'] = $node;
			
			$form = $this->buildDeleteForm( $node );

			$form->handleRequest();

			if ($form->isValid() && 
				$form->getData()['node_id'] == $node->getId() ) {

				$node->getHandler()->removeWithChildrenAndAssociations();

				$msg = $this->getTranslator()->trans('node.deleted', array('%name%'=>$node->getNodeName()));
				$request->getSession()->getFlashBag()->add('confirm', $msg);
	 			$this->getLogger()->info($msg);
		 		/*
		 		 * Force redirect to avoid resending form when refreshing page
		 		 */
		 		$response = new RedirectResponse(
					Kernel::getInstance()->getUrlGenerator()->generate('nodesHomePage')
				);
				$response->prepare($request);

				return $response->send();
			}

			$this->assignation['form'] = $form->createView();

			return new Response(
				$this->getTwig()->render('nodes/delete.html.twig', $this->assignation),
				Response::HTTP_OK,
				array('content-type' => 'text/html')
			);
		}
		else {
			return $this->throw404();
		}
	}

	/**
	 * 
	 * @param  array $data 
	 * @return RZ\Renzo\Core\Entities\Node
	 */
	private function createNode( $data, NodeType $type, Translation $translation )
	{
		if ($this->urlAliasExists( StringHandler::slugify($data['nodeName']) )) {
			$msg = $this->getTranslator()->trans('node.no_creation.url_alias.already_exists', array('%name%'=>$data['nodeName']));
			throw new EntityAlreadyExistsException($msg, 1);
		}

		try {
			$node = new Node( $type );
			$node->setNodeName($data['nodeName']);
			Kernel::getInstance()->em()->persist($node);

			$sourceClass = "GeneratedNodeSources\\".$type->getSourceEntityClassName();
			$source = new $sourceClass($node, $translation);
			Kernel::getInstance()->em()->persist($source);
			Kernel::getInstance()->em()->flush();
			return $node;
		}
		catch( \Exception $e ){
			$msg = $this->getTranslator()->trans('node.no_creation.already_exists', array('%name%'=>$node->getNodeName()));
			throw new EntityAlreadyExistsException($msg, 1);
		}
	}

	private function urlAliasExists( $name )
	{
		return (boolean)Kernel::getInstance()->em()
			->getRepository('RZ\Renzo\Core\Entities\UrlAlias')
			->exists( $name );
	}
	private function nodeNameExists( $name )
	{
		return (boolean)Kernel::getInstance()->em()
			->getRepository('RZ\Renzo\Core\Entities\Node')
			->exists( $name );
	}

	/**
	 * Edit node base parameters
	 * 
	 * @param  array $data Form data
	 * @param  Node   $node [description]
	 * @return void
	 */
	private function editNode( $data, Node $node)
	{	
		$testingNodeName = StringHandler::slugify($data['nodeName']);
		if ($testingNodeName != $node->getNodeName() && 
				($this->nodeNameExists($testingNodeName) || 
				$this->urlAliasExists($testingNodeName))) {

			$msg = $this->getTranslator()->trans('node.no_update.already_exists', array('%name%'=>$data['nodeName']));
			throw new EntityAlreadyExistsException($msg , 1);
		}
		foreach ($data as $key => $value) {
			$setter = 'set'.ucwords($key);
			$node->$setter( $value );
		}

		Kernel::getInstance()->em()->flush();
	}

	/**
	 * Link a node with a tag 
	 * 
	 * @param  array $data Form data
	 * @param  Node   $node [description]
	 * @return Tag $linkedTag
	 */
	private function addNodeTag($data, Node $node)
	{
		$tag = Kernel::getInstance()->em()
				->getRepository('RZ\Renzo\Core\Entities\Tag')
				->findWithDefaultTranslation($data['tag_id']);

		$node->getTags()->add($tag);
		Kernel::getInstance()->em()->flush();

		return $tag;
	}

	/**
	 * Create a new node-source for given translation
	 * 
	 * 
	 * @param  array $data Form data
	 * @param  Node   $node [description]
	 * @return void
	 */
	private function translateNode( $data, Node $node )
	{
		$sourceClass = "GeneratedNodeSources\\".$node->getNodeType()->getSourceEntityClassName();
		$new_translation = Kernel::getInstance()->em()
				->find('RZ\Renzo\Core\Entities\Translation', (int)$data['translation_id']);


		$source = new $sourceClass($node, $new_translation);

		Kernel::getInstance()->em()->persist($source);
		Kernel::getInstance()->em()->flush();
	}

	/**
	 * Edit node source parameters
	 * 
	 * 
	 * @param  array $data Form data
	 * @param  $nodeSource
	 * @return void    
	 */
	private function editNodeSource( $data, $nodeSource )
	{
		$fields = $nodeSource->getNode()->getNodeType()->getFields();
		foreach ($fields as $field) {
			if (isset($data[$field->getName()])) {

				$setter = $field->getSetterName();
				$nodeSource->$setter( $data[$field->getName()] );
			}
		}

		Kernel::getInstance()->em()->flush();
	}

	/**
	 * [addNodeUrlAlias description]
	 * @param [type] $data [description]
	 * @param Node   $node
	 * @return UrlAlias
	 */
	private function addNodeUrlAlias( $data, Node $node )
	{
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
	 * 
	 * @param  array   $data Form data
	 * @param  UrlAlias $ua 
	 * @return void
	 */
	private function editUrlAlias( $data, UrlAlias $ua )
	{
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
	 * 
	 * @param  array   $data Form data
	 * @param  UrlAlias $ua 
	 * @return void
	 */
	private function deleteUrlAlias( $data, UrlAlias $ua )
	{
		if ($data['urlalias_id'] == $ua->getId()) {
			
			Kernel::getInstance()->em()->remove($ua);
			Kernel::getInstance()->em()->flush();
		}
	}

	private function buildTranslateForm( Node $node )
	{
		$translations = $node->getHandler()->getUnavailableTranslations();
		$choices = array();

		foreach ($translations as $translation) {
			$choices[$translation->getId()] = $translation->getName();
		}

		if ($translations !== null && count($choices) > 0) {

			$builder = $this->getFormFactory()
				->createBuilder('form')
				->add('node_id', 'hidden', array(
					'data' => $node->getId(),
					'constraints' => array(
						new NotBlank()
					)
				))
				->add('translation_id', 'choice', array(
					'choices' => $choices,
					'required' => true
				))
			;

			return $builder->getForm();
		}
		else {
			return null;
		}
	}

	/**
	 * 
	 * @param  Node   $node [description]
	 * @return Symfony\Component\Form\Forms
	 */
	private function buildAddUrlAliasForm( Node $node )
	{
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
	 * 
	 * @param  UrlAlias $ua
	 * @return Symfony\Component\Form\Forms
	 */
	private function buildEditUrlAliasForm( UrlAlias $ua )
	{
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
	 * 
	 * @param  UrlAlias $ua
	 * @return Symfony\Component\Form\Forms
	 */
	private function buildDeleteUrlAliasForm( UrlAlias $ua )
	{
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

	/**
	 * 
	 * @param  Node   $node 
	 * @return Symfony\Component\Form\Forms
	 */
	private function buildEditForm( Node $node )
	{
		$fields = $node->getNodeType()->getFields();

		$defaults = array(
			'nodeName' =>  $node->getNodeName(),
			'visible' =>   $node->isVisible(),
			'locked' =>    $node->isLocked(),
			'published' => $node->isPublished(),
			'archived' =>  $node->isArchived(),
		);
		$builder = $this->getFormFactory()
					->createBuilder('form', $defaults)
					->add('nodeName', 'text', array(
						'constraints' => array(
							new NotBlank()
						)
					))
					->add('visible',   'checkbox', array('required' => false))
					->add('locked',    'checkbox', array('required' => false))
					->add('published', 'checkbox', array('required' => false))
					->add('archived',  'checkbox', array('required' => false));

		return $builder->getForm();
	}

	/**
	 * 
	 * @param  Node   $node 
	 * @return Symfony\Component\Form\Forms
	 */
	private function buildEditTagsForm( Node $node )
	{
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
					->add('tag_id', new \RZ\Renzo\CMS\Forms\TagsType() );

		return $builder->getForm();
	}

	/**
	 * 
	 * @param  Node  $node
	 * @param  NodesSources $source
	 * @return Symfony\Component\Form\Forms
	 */
	private function buildEditSourceForm( Node $node, $source )
	{
		$fields = $node->getNodeType()->getFields();
		/*
		 * Create source default values
		 */
		$sourceDefaults = array();
		foreach ($fields as $field) {
			$getter = $field->getGetterName();
			$sourceDefaults[$field->getName()] = $source->$getter();
		}	

		/*
		 * Create subform for source
		 */
		$sourceBuilder = $this->getFormFactory()
					->createNamedBuilder('source','form', $sourceDefaults);
		foreach ($fields as $field) {
			$sourceBuilder->add(
				$field->getName(), 
				static::getFormTypeFromFieldType( $field ), 
				array(
					'label'  => $field->getLabel(),
					'required' => false
				)
			);
		}
		return $sourceBuilder->getForm();
	}

	/**
	 * 
	 * @param  string $type
	 * @return AbstractType
	 */
	public static function getFormTypeFromFieldType( NodeTypeField $field )
	{
		switch ($field->getType()) {
			case NodeTypeField::MARKDOWN_T:
				return new \RZ\Renzo\CMS\Forms\MarkdownType();
			
			default:
				return NodeTypeField::$typeToForm[$field->getType()];
		}
	}

	/**
	 * 
	 * @param  Node   $node 
	 * @return Symfony\Component\Form\Forms
	 */
	private function buildDeleteForm( Node $node )
	{
		$builder = $this->getFormFactory()
			->createBuilder('form')
			->add('node_id', 'hidden', array(
				'data' => $node->getId(),
				'constraints' => array(
					new NotBlank()
				)
			))
		;

		return $builder->getForm();
	}
}