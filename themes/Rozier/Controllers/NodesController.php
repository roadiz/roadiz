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
use RZ\Renzo\Core\Entities\Tag;
use RZ\Renzo\Core\Entities\NodeType;
use RZ\Renzo\Core\Entities\NodeTypeField;
use RZ\Renzo\Core\Entities\UrlAlias;
use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Core\Handlers\NodeHandler;
use RZ\Renzo\Core\Utils\StringHandler;
use RZ\Renzo\Core\ListManagers\EntityListManager;

use Themes\Rozier\Widgets\NodeTreeWidget;
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

class NodesController extends RozierApp {

	const ITEM_PER_PAGE = 5;

	/**
	 * List every nodes.
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function indexAction(Request $request) {
		
		/*
		 * Security
		 */
		// show different content to admin users
	    /*if (false === static::getSecurityContext()->isGranted('ROLE_NODES_EDITOR')) {
	        throw new \Symfony\Component\Security\Core\Exception\AccessDeniedException();
	    }*/

		$translation = Kernel::getInstance()->em()
				->getRepository('RZ\Renzo\Core\Entities\Translation')
        		->findDefault();

        /*
		 * Manage get request to filter list
		 */
		$listManager = new EntityListManager( 
			$request, 
			Kernel::getInstance()->em(), 
			'RZ\Renzo\Core\Entities\Node'
		);
		$listManager->handle();

		$this->assignation['filters'] = $listManager->getAssignation();
		$this->assignation['nodes'] =   $listManager->getEntities();

		$this->assignation['node_types'] = NodeTypesController::getNodeTypes();
		$this->assignation['translation'] = $translation;

		return new Response(
			$this->getTwig()->render('nodes/list.html.twig', $this->assignation),
			Response::HTTP_OK,
			array('content-type' => 'text/html')
		);
	}

	/**
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @param  int  $node_id
	 * @param  int  $translation_id
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function treeAction(Request $request, $node_id, $translation_id = null) {
		$node = Kernel::getInstance()->em()
			->find('RZ\Renzo\Core\Entities\Node', (int)$node_id);
		Kernel::getInstance()->em()->refresh($node);

		$translation = null;
		if ($translation_id !== null) {
			$translation = Kernel::getInstance()->em()
				->getRepository('RZ\Renzo\Core\Entities\Translation')
				->findOneBy(array('id'=>(int)$translation_id));
		}
		else {
			$translation = Kernel::getInstance()->em()
					->getRepository('RZ\Renzo\Core\Entities\Translation')
        			->findDefault();
		}

		if ($node !== null) {
			$widget = new NodeTreeWidget( $request, $this, $node, $translation );
			$this->assignation['node'] = $node;
			$this->assignation['source'] = $node->getNodeSources()->first();
			$this->assignation['translation'] = $translation;
			$this->assignation['specificNodeTree'] = $widget;
		}


		return new Response(
			$this->getTwig()->render('nodes/tree.html.twig', $this->assignation),
			Response::HTTP_OK,
			array('content-type' => 'text/html')
		);
	}

	/**
	 * Return an edition form for requested node.
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @param  int  $node_id
	 * @param  int  $translation_id
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function editAction(Request $request, $node_id, $translation_id = null) {
		$node = Kernel::getInstance()->em()
			->find('RZ\Renzo\Core\Entities\Node', (int)$node_id);
		Kernel::getInstance()->em()->refresh($node);

		$translation = Kernel::getInstance()->em()
				->getRepository('RZ\Renzo\Core\Entities\Translation')
        		->findDefault();
		
		if ($node !== null) {
			$this->assignation['node'] = $node;
			$this->assignation['source'] = $node->getNodeSources()->first();
			$this->assignation['translation'] = $translation;

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
	 * Return an edition form for requested node.
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @param  int  $node_id
	 * @param  int  $translation_id
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function editSourceAction(Request $request, $node_id, $translation_id) {
		$translation = Kernel::getInstance()->em()
				->find('RZ\Renzo\Core\Entities\Translation', (int)$translation_id);

		if ($translation !== null) {

			$gnode = Kernel::getInstance()->em()
				->find('RZ\Renzo\Core\Entities\Node', (int)$node_id);

			$source = Kernel::getInstance()->em()
				->getRepository('RZ\Renzo\Core\Entities\NodesSources')
				->findOneBy(array('translation'=>$translation, 'node'=>array('id'=>(int)$node_id)));

			if ($source !== null && 
				$translation !== null) {

				$node = $source->getNode();

				$this->assignation['translation'] = $translation;
				$this->assignation['available_translations'] = $gnode->getHandler()->getAvailableTranslations();
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
				//Kernel::getInstance()->em()->detach($node);

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
	 * Return tags form for requested node.
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @param  int  $node_id
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function editTagsAction(Request $request, $node_id) {
		$translation = Kernel::getInstance()->em()
				->getRepository('RZ\Renzo\Core\Entities\Translation')
        		->findDefault();

		if ($translation !== null) {

			$source = Kernel::getInstance()->em()
				->getRepository('RZ\Renzo\Core\Entities\NodesSources')
				->findOneBy(array('translation'=>$translation, 'node'=>array('id'=>(int)$node_id)));

			if ($source !== null && 
				$translation !== null) {

				$node = $source->getNode();

				$this->assignation['translation'] = $translation;
				$this->assignation['node'] = 		$node;
				$this->assignation['source'] = 		$source;
				
				$form = $this->buildEditTagsForm( $node );

				$form->handleRequest();

				if ($form->isValid()) {
			 		$tag = $this->addNodeTag($form->getData(), $node);

			 		$msg = $this->getTranslator()->trans('node.tag_linked', array(
			 			'%node%'=>$node->getNodeName(), 
			 			'%tag%'=>$tag->getTranslatedTags()->first()->getName()
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
	 * Return a deletion form for requested tag depending on the node.
	 * @param  Symfony\Component\HttpFoundation\Requet  $request
	 * @param  int  $node_id
	 * @param  int  $tag_id
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function removeTagAction(Request $request, $node_id, $tag_id) {
		$node = Kernel::getInstance()->em()
			->find('RZ\Renzo\Core\Entities\Node', (int)$node_id);
		$tag = Kernel::getInstance()->em()
			->find('RZ\Renzo\Core\Entities\Tag', (int)$tag_id);

		if ($node !== null && $tag !== null) {
			$this->assignation['node'] = $node;
			$this->assignation['tag'] = $tag;

			$form = $this->buildRemoveTagForm($node, $tag);
			$form->handleRequest();

			if ($form->isValid()) {

		 		$this->removeNodeTag($form->getData(), $node, $tag);
		 		$msg = $this->getTranslator()->trans('tag.removed', array('%name%' => $tag->getTranslatedTags()->first()->getName()));
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
				$this->getTwig()->render('nodes/removeTag.html.twig', $this->assignation),
				Response::HTTP_OK,
				array('content-type' => 'text/html')
			);
		}
		else {
			return $this->throw404();
		}
	}

	/**
	 * Handle node creation pages.
	 * @param Symfony\Component\HttpFoundation\Request  $request
	 * @param int  $node_type_id
	 * @param int  $translation_id
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function addAction(Request $request, $node_type_id, $translation_id = null) {	
		$type = Kernel::getInstance()->em()
				->find('RZ\Renzo\Core\Entities\NodeType', $node_type_id);

		$translation = Kernel::getInstance()->em()
				->getRepository('RZ\Renzo\Core\Entities\Translation')
        		->findDefault();

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
	 * Handle node creation pages.
	 * @param Symfony\Component\HttpFoundation\Request  $request
	 * @param int  $node_id
	 * @param int  $translation_id
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function addChildAction(Request $request, $node_id, $translation_id = null) {	
		$translation = Kernel::getInstance()->em()
				->getRepository('RZ\Renzo\Core\Entities\Translation')
        		->findDefault();

		if ($translation_id != null) {
			$translation = Kernel::getInstance()->em()
				->find('RZ\Renzo\Core\Entities\Translation', (int)$translation_id);
		}
		$parentNode = Kernel::getInstance()->em()
			->find('RZ\Renzo\Core\Entities\Node', (int)$node_id);

		if ($translation !== null && 
			$parentNode !== null) {

			$form = $this->buildAddChildForm( $parentNode, $translation );
			$form->handleRequest();

			if ($form->isValid()) {

				try {
					$node = $this->createChildNode($form->getData(), $parentNode, $translation);

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
							'nodesAddChildPage',
							array('node_id' => $node_id, 'translation_id' => $translation_id)
						)
					);
					$response->prepare($request);
					return $response->send();
				}
			}

			$this->assignation['translation'] = $translation;
			$this->assignation['form'] = $form->createView();
			$this->assignation['parentNode'] = $parentNode;

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
	 * Return an deletion form for requested node.
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @param  int  $node_id
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function deleteAction(Request $request, $node_id) {
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
	 * @param  array  $data
	 * @param  RZ\Renzo\Core\Entities\NodeType  $type
	 * @param  RZ\Renzo\Core\Entities\Translation  $translation
	 * @return RZ\Renzo\Core\Entities\Node
	 */
	private function createNode($data, NodeType $type, Translation $translation) {
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

	/**
	 * @param  array  $data 
	 * @return RZ\Renzo\Core\Entities\Node
	 */
	private function createChildNode($data, Node $parentNode, Translation $translation) {
		if ($this->urlAliasExists( StringHandler::slugify($data['nodeName']) )) {
			$msg = $this->getTranslator()->trans('node.no_creation.url_alias.already_exists', array('%name%'=>$data['nodeName']));
			throw new EntityAlreadyExistsException($msg, 1);
		}
		$type = null;

		if (!empty($data['node_type_id'])) {
			$type = Kernel::getInstance()->em()
						->find('RZ\Renzo\Core\Entities\NodeType', (int)$data['node_type_id']);
		}
		if ($type === null) {
			throw new \Exception("Cannot create a node without a valid node-type", 1);
		}
		if ($data['parent_id'] != $parentNode->getId()) {
			throw new \Exception("Requested parent node does not match form values", 1);
		}

		try {
			$node = new Node( $type );
			$node->setParent($parentNode);
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

	/**
	 * @param  string  $name
	 * @return void
	 */
	private function urlAliasExists($name) {
		return (boolean)Kernel::getInstance()->em()
			->getRepository('RZ\Renzo\Core\Entities\UrlAlias')
			->exists( $name );
	}

	/**
	 * 
	 * @param  string $name
	 * @return void
	 */
	private function nodeNameExists($name) {
		return (boolean)Kernel::getInstance()->em()
			->getRepository('RZ\Renzo\Core\Entities\Node')
			->exists( $name );
	}

	/**
	 * Edit node base parameters.
	 * @param  array  $data 
	 * @param  Node   $node 
	 * @return void
	 */
	private function editNode($data, Node $node) {	
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
	 * Link a node with a tag.
	 * @param  array $data Form data
	 * @param  RZ\Renzo\Core\Entities\Node  $node 
	 * @return RZ\Renzo\Core\Entities\Tag  $linkedTag
	 */
	private function addNodeTag($data, Node $node) {
		$tag = Kernel::getInstance()->em()
				->getRepository('RZ\Renzo\Core\Entities\Tag')
				->findWithDefaultTranslation($data['tag_id']);

		$node->getTags()->add($tag);
		Kernel::getInstance()->em()->flush();

		return $tag;
	}

	/**
	 * @param  array  $data
	 * @param  RZ\Renzo\Core\Entities\Node  $node
	 * @param  RZ\Renzo\Core\Entities\Tag  $tag
	 * @return RZ\Renzo\Core\Entities\Tag
	 */
	private function removeNodeTag($data, Node $node, Tag $tag) {
		if ($data['node_id'] == $node->getId() && 
			$data['tag_id'] == $tag->getId()) {

			$node->removeTag($tag);
			Kernel::getInstance()->em()->flush();	

			return ($tag);
		}
	}

	/**
	 * Create a new node-source for given translation.
	 * @param  array  $data 
	 * @param  RZ\Renzo\Core\Entities\Node  $node 
	 * @return void
	 */
	private function translateNode($data, Node $node) {
		$sourceClass = "GeneratedNodeSources\\".$node->getNodeType()->getSourceEntityClassName();
		$new_translation = Kernel::getInstance()->em()
				->find('RZ\Renzo\Core\Entities\Translation', (int)$data['translation_id']);


		$source = new $sourceClass($node, $new_translation);

		Kernel::getInstance()->em()->persist($source);
		Kernel::getInstance()->em()->flush();
	}

	/**
	 * Edit node source parameters.
	 * @param  array  $data 
	 * @param  RZ\Renzo\Core\Entities\NodesSources  $nodeSource
	 * @return void    
	 */
	private function editNodeSource($data, $nodeSource) {
		$fields = $nodeSource->getNode()->getNodeType()->getFields();
		foreach ($fields as $field) {
			if (isset($data[$field->getName()])) {
				static::setValueFromFieldType($data, $nodeSource, $field);
			}
		}

		Kernel::getInstance()->em()->flush();
	}

	/**
	 * @param  RZ\Renzo\Core\Entities\Node  $node
	 * @return 
	 */
	private function buildTranslateForm(Node $node) {
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
	 * @param  RZ\Renzo\Core\Entities\Node  $parentNode 
	 * @return \Symfony\Component\Form\Form
	 */
	private function buildAddChildForm(Node $parentNode) {
		$defaults = array(
			
		);
		$builder = $this->getFormFactory()
			->createBuilder('form', $defaults)
			->add('nodeName', 'text', array(
				'constraints' => array(
					new NotBlank()
				)
			))
			->add('parent_id', 'hidden', array(
				'data'=>(int)$parentNode->getId(),
				'constraints' => array(
					new NotBlank()
				)
			))
			->add('node_type_id', new \RZ\Renzo\CMS\Forms\NodeTypesType())
		;

		return $builder->getForm();
	}

	/**
	 * @param  RZ\Renzo\Core\Entities\Node  $node 
	 * @return \Symfony\Component\Form\Form
	 */
	private function buildEditForm(Node $node) {
		$fields = $node->getNodeType()->getFields();

		$defaults = array(
			'nodeName' =>  $node->getNodeName(),
			'home' =>      $node->isHome(),
			'hidingChildren' => $node->isHidingChildren(),
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
					->add('home',      'checkbox', array('required' => false))
					->add('hidingChildren', 'checkbox', array('required' => false))
					->add('visible',   'checkbox', array('required' => false))
					->add('locked',    'checkbox', array('required' => false))
					->add('published', 'checkbox', array('required' => false))
					->add('archived',  'checkbox', array('required' => false));

		return $builder->getForm();
	}

	/**
	 * @param  RZ\Renzo\Core\Entities\Node  $node 
	 * @return \Symfony\Component\Form\Form
	 */
	private function buildEditTagsForm(Node $node) {
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
					->add('tag_id', new \RZ\Renzo\CMS\Forms\TagsType($node->getTags()) );

		return $builder->getForm();
	}

	/**
	 * @param  RZ\Renzo\Core\Entities\Node  $node
	 * @param  RZ\Renzo\Core\Entities\NodesSources  $source
	 * @return \Symfony\Component\Form\Form
	 */
	private function buildEditSourceForm(Node $node, $source) {
		$fields = $node->getNodeType()->getFields();
		/*
		 * Create source default values
		 */
		$sourceDefaults = array();
		foreach ($fields as $field) {
			if (!$field->isVirtual()) {
				$getter = $field->getGetterName();
				$sourceDefaults[$field->getName()] = $source->$getter();
			}
		}	

		/*
		 * Create subform for source
		 */
		$sourceBuilder = $this->getFormFactory()
					->createNamedBuilder('source','form', $sourceDefaults);
		foreach ($fields as $field) {
			$sourceBuilder->add(
				$field->getName(), 
				static::getFormTypeFromFieldType( $source, $field ), 
				array(
					'label'  => $field->getLabel(),
					'required' => false
				)
			);
		}
		return $sourceBuilder->getForm();
	}

	/**
	 * @param  string  $type
	 * @return AbstractType
	 */
	public static function getFormTypeFromFieldType($nodeSource, NodeTypeField $field) {
		switch ($field->getType()) {
			case NodeTypeField::DOCUMENTS_T:
				$documents = $nodeSource->getHandler()->getDocumentsFromFieldName( $field->getName() );
				return new \RZ\Renzo\CMS\Forms\DocumentsType( $documents );

			case NodeTypeField::MARKDOWN_T:
				return new \RZ\Renzo\CMS\Forms\MarkdownType();
			
			default:
				return NodeTypeField::$typeToForm[$field->getType()];
		}
	}

	/**
	 * Fill node-source content according to field type.
	 * @param array  $data       
	 * @param NodesSources  $nodeSource 
	 * @param NodeTypeField  $field 
	 * @return void
	 */
	public static function setValueFromFieldType($data, $nodeSource, NodeTypeField $field) {
		switch ($field->getType()) {
			case NodeTypeField::DOCUMENTS_T:
				$nodeSource->getHandler()->cleanDocumentsFromField($field);

				foreach ($data[$field->getName()] as $documentId) {
					$tempDoc = Kernel::getInstance()->em()
						->find('RZ\Renzo\Core\Entities\Document', (int)$documentId);
					if ($tempDoc !== null) {
						$nodeSource->getHandler()->addDocumentForField($tempDoc, $field);
					}
				}
				break;
			default:
				$setter = $field->getSetterName();
				$nodeSource->$setter( $data[$field->getName()] );
				break;
		}
	}

	/**
	 * @param  RZ\Renzo\Core\Entities\Node  $node 
	 * @return \Symfony\Component\Form\Form
	 */
	private function buildDeleteForm(Node $node) {
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

	/**
	 * @param RZ\Renzo\Core\Entities\Node  $node
	 * @param RZ\Renzo\Core\Entities\Tag  $tag
	 * @return \Symfony\Component\Form\Form
	 */
	private function buildRemoveTagForm(Node $node, Tag $tag) {
		$builder = $this->getFormFactory()
			->createBuilder('form')
			->add('node_id', 'hidden', array(
				'data' => $node->getId(),
				'constraints' => array(
					new NotBlank()
				)
			))
			->add('tag_id', 'hidden', array(
				'data' => $tag->getId(),
				'constraints' => array(
					new NotBlank()
				)
			))
		;

		return $builder->getForm();
	}
}