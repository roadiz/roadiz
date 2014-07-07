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

use Themes\Rozier\RozierApp;
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
	public function indexAction()
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
	public function editAction( $node_id, $translation_id = null )
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
			 		$this->translateNode($translation_form->getData(), $node);

			 		/*
			 		 * Force redirect to avoid resending form when refreshing page
			 		 */
			 		$response = new RedirectResponse(
						Kernel::getInstance()->getUrlGenerator()->generate(
							'nodesEditSourcePage',
							array('node_id' => $node->getId(), 'translation_id'=>$translation_form->getData()['translation_id'])
						)
					);
					$response->prepare(Kernel::getInstance()->getRequest());

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
		 		$this->editNode($form->getData(), $node);

		 		/*
		 		 * Force redirect to avoid resending form when refreshing page
		 		 */
		 		$response = new RedirectResponse(
					Kernel::getInstance()->getUrlGenerator()->generate(
						'nodesEditPage',
						array('node_id' => $node->getId())
					)
				);
				$response->prepare(Kernel::getInstance()->getRequest());

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
	public function editSourceAction( $node_id, $translation_id = null )
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
				
				$form = $this->buildEditSourceForm( $node, $source );

				$form->handleRequest();

				if ($form->isValid()) {
			 		$this->editNodeSource($form->getData(), $source);

			 		/*
			 		 * Force redirect to avoid resending form when refreshing page
			 		 */
			 		$response = new RedirectResponse(
						Kernel::getInstance()->getUrlGenerator()->generate(
							'nodesEditSourcePage',
							array('node_id' => $node->getId(), 'translation_id'=>$translation->getId())
						)
					);
					$response->prepare(Kernel::getInstance()->getRequest());

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
	public function editTagsAction( $node_id )
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
			 		$this->addNodeTag($form->getData(), $node);

			 		/*
			 		 * Force redirect to avoid resending form when refreshing page
			 		 */
			 		$response = new RedirectResponse(
						Kernel::getInstance()->getUrlGenerator()->generate(
							'nodesEditTagsPage',
							array('node_id' => $node->getId())
						)
					);
					$response->prepare(Kernel::getInstance()->getRequest());

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
	public function editAliasesAction( $node_id )
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

					$this->editUrlAlias($editForm->getData(), $alias);
					/*
			 		 * Force redirect to avoid resending form when refreshing page
			 		 */
			 		$response = new RedirectResponse(
						Kernel::getInstance()->getUrlGenerator()->generate(
							'nodesEditAliasesPage',
							array('node_id' => $node->getId())
						)
					);
					$response->prepare(Kernel::getInstance()->getRequest());

					return $response->send();
				}
				// Match delete
				$deleteForm->handleRequest();
				if ($deleteForm->isValid()) {

					$this->deleteUrlAlias($editForm->getData(), $alias);
					/*
			 		 * Force redirect to avoid resending form when refreshing page
			 		 */
			 		$response = new RedirectResponse(
						Kernel::getInstance()->getUrlGenerator()->generate(
							'nodesEditAliasesPage',
							array('node_id' => $node->getId())
						)
					);
					$response->prepare(Kernel::getInstance()->getRequest());

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
		 		$this->addNodeUrlAlias($form->getData(), $node);

		 		/*
		 		 * Force redirect to avoid resending form when refreshing page
		 		 */
		 		$response = new RedirectResponse(
					Kernel::getInstance()->getUrlGenerator()->generate(
						'nodesEditAliasesPage',
						array('node_id' => $node->getId())
					)
				);
				$response->prepare(Kernel::getInstance()->getRequest());

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
	public function addAction( $node_type_id, $translation_id = null )
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

				$node = $this->createNode($form->getData(), $type, $translation);
				if ($node !== null) {
					$response = new RedirectResponse(
						Kernel::getInstance()->getUrlGenerator()->generate(
							'nodesEditPage',
							array('node_id' => $node->getId())
						)
					);
					$response->prepare(Kernel::getInstance()->getRequest());

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
	public function deleteAction( $node_id )
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
				$this->getSession()->getFlashBag()->add('confirm', 'Node has been deleted');
		 		/*
		 		 * Force redirect to avoid resending form when refreshing page
		 		 */
		 		$response = new RedirectResponse(
					Kernel::getInstance()->getUrlGenerator()->generate('nodesHomePage')
				);
				$response->prepare(Kernel::getInstance()->getRequest());

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
		try {

			$node = new Node( $type );
			$node->setNodeName($data['nodeName']);
			Kernel::getInstance()->em()->persist($node);

			$sourceClass = "GeneratedNodeSources\\".$type->getSourceEntityClassName();
			$source = new $sourceClass($node, $translation);
			Kernel::getInstance()->em()->persist($source);

			Kernel::getInstance()->em()->flush();
			$this->getSession()->getFlashBag()->add('confirm', 'Node “'.$node->getNodeName().'” has been created');

			return $node;
		}
		catch( \Exception $e ){
			$this->getSession()->getFlashBag()->add('error', 'Node “'.$node->getNodeName().'” already exists.');
			return null;
		}
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
		foreach ($data as $key => $value) {
			$setter = 'set'.ucwords($key);
			$node->$setter( $value );
		}

		Kernel::getInstance()->em()->flush();
		$this->getSession()->getFlashBag()->add('confirm', 'Node “'.$node->getNodeName().'” has been updated');
	}

	/**
	 * Link a node with a tag 
	 * 
	 * @param  array $data Form data
	 * @param  Node   $node [description]
	 * @return void
	 */
	private function addNodeTag($data, Node $node)
	{
		$tag = Kernel::getInstance()->em()
				->getRepository('RZ\Renzo\Core\Entities\Tag')
				->findWithDefaultTranslation($data['tag_id']);

		$node->getTags()->add($tag);
		Kernel::getInstance()->em()->flush();

		$this->getSession()->getFlashBag()->add('confirm', 'Tag “'.$tag->getDefaultTranslatedTag()->getName().'” has been linked to node “'.$node->getNodeName().'”.');
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

		$this->getSession()->getFlashBag()->add('confirm', 'Node “'.$node->getNodeName().'” has been translated');
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
		$this->getSession()->getFlashBag()->add('confirm', 'Node “'.$nodeSource->getNode()->getNodeName().'” content for “'.$nodeSource->getTranslation()->getName().'” has been updated');
	}

	/**
	 * [addNodeUrlAlias description]
	 * @param [type] $data [description]
	 * @param Node   $node
	 * @return void 
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
				
				try {

					$ua = new UrlAlias( $nodeSource );
					$ua->setAlias($data['alias']);
					Kernel::getInstance()->em()->persist($ua);
					Kernel::getInstance()->em()->flush();
					$this->getSession()->getFlashBag()->add('confirm', 'Url alias “'.$ua->getAlias().'” for “'.$translation->getName().'” translation has been created');
				}
				catch(\Exception $e){
					$this->getSession()->getFlashBag()->add('error', 'Url alias can’t be created, it already exists.');
				}
			}
			else{
				$this->getSession()->getFlashBag()->add('error', 'Url alias can’t be created, node has no “'.$translation->getName().'” translation.');
			}
		}
	}

	/**
	 * 
	 * @param  array   $data Form data
	 * @param  UrlAlias $ua 
	 * @return void
	 */
	private function editUrlAlias( $data, UrlAlias $ua )
	{
		if ($data['urlalias_id'] == $ua->getId()) {
			
			try {
				$ua->setAlias($data['alias']);
				Kernel::getInstance()->em()->flush();
				$this->getSession()->getFlashBag()->add('confirm', 'Url alias “'.$ua->getAlias().'” has been updated.');
			}
			catch(\Exception $e){
				$this->getSession()->getFlashBag()->add('error', 'Url alias can’t be updated, alias already exists.');
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
			$this->getSession()->getFlashBag()->add('confirm', 'Url alias “'.$ua->getAlias().'” has been removed.');
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