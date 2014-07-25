<?php 
/**
 * Copyright REZO ZERO 2014
 * 
 * 
 * 
 *
 * @file NodeTypesController.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Controllers;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Node;
use RZ\Renzo\Core\Entities\NodeType;
use RZ\Renzo\Core\Entities\NodeTypeField;
use RZ\Renzo\Core\Entities\Translation;
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
class NodeTypesController extends RozierApp
{
	/**
	 * List every node-types
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function indexAction( Request $request )
	{
		$node_types = Kernel::getInstance()->em()
			->getRepository('RZ\Renzo\Core\Entities\NodeType')
			->findAll();

		$this->assignation['node_types'] = $node_types;

		return new Response(
			$this->getTwig()->render('node-types/list.html.twig', $this->assignation),
			Response::HTTP_OK,
			array('content-type' => 'text/html')
		);
	}

	/**
	 * Return an edition form for requested node-type
	 * @param  integer $node_type_id        [description]
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function editAction( Request $request, $node_type_id )
	{
		$node_type = Kernel::getInstance()->em()
			->find('RZ\Renzo\Core\Entities\NodeType', (int)$node_type_id);

		if ($node_type !== null) {
			$this->assignation['node_type'] = $node_type;
			
			$form = $this->buildEditForm( $node_type );

			$form->handleRequest();

			if ($form->isValid()) {
				try{
			 		$this->editNodeType($form->getData(), $node_type);

			 		$msg = $this->getTranslator()->trans('node_type.updated', array('%name%'=>$node_type->getName()));
			 		$request->getSession()->getFlashBag()->add('confirm', $msg);
	 				$this->getLogger()->info($msg);
				}
				catch (EntityAlreadyExistsException $e) {
					$request->getSession()->getFlashBag()->add('error', $e->getMessage());
	 				$this->getLogger()->warning($e->getMessage());
				}
		 		/*
		 		 * Redirect to update schema page
		 		 */
		 		$response = new RedirectResponse(
					Kernel::getInstance()->getUrlGenerator()->generate(
						'nodeTypesSchemaUpdate', 
						array(
							'_token' => static::$csrfProvider->generateCsrfToken(static::SCHEMA_TOKEN_INTENTION)
						)
					)
				);
				$response->prepare($request);

				return $response->send();
			}

			$this->assignation['form'] = $form->createView();

			return new Response(
				$this->getTwig()->render('node-types/edit.html.twig', $this->assignation),
				Response::HTTP_OK,
				array('content-type' => 'text/html')
			);
		}
		else {
			return $this->throw404();
		}
	}

	/**
	 * Return an creation form for requested node-type
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function addAction( Request $request )
	{
		$node_type = new NodeType();

		if ($node_type !== null) {
			$this->assignation['node_type'] = $node_type;
			
			/*
			 * form
			 */
			$form = $this->buildAddForm( $node_type );
			$form->handleRequest();
			if ($form->isValid()) {
				try {
					//echo "Before add node type";
			 		$this->addNodeType($form->getData(), $node_type);
			 		//echo "After add node type";
			 		
			 		$msg = $this->getTranslator()->trans('node_type.created', array('%name%'=>$node_type->getName()));
				 	$request->getSession()->getFlashBag()->add('confirm', $msg);
	 				$this->getLogger()->info($msg);

			 		/*
			 		 * Redirect to update schema page
			 		 */
			 		$response = new RedirectResponse(
						Kernel::getInstance()->getUrlGenerator()->generate(
							'nodeTypesSchemaUpdate', 
							array(
								'_token' => static::$csrfProvider->generateCsrfToken(static::SCHEMA_TOKEN_INTENTION)
							)
						)
					);
					
				} catch (EntityAlreadyExistsException $e) {
					$request->getSession()->getFlashBag()->add('error', $e->getMessage());
	 				$this->getLogger()->warning($e->getMessage());
					$response = new RedirectResponse(
						Kernel::getInstance()->getUrlGenerator()->generate(
							'nodeTypesAddPage'
						)
					);
				}
				$response->prepare($request);
				return $response->send();
			}

			$this->assignation['form'] = $form->createView();

			return new Response(
				$this->getTwig()->render('node-types/add.html.twig', $this->assignation),
				Response::HTTP_OK,
				array('content-type' => 'text/html')
			);
		}
		else {
			return $this->throw404();
		}
	}

	/**
	 * Return an deletion form for requested node-type
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function deleteAction( Request $request, $node_type_id )
	{
		$node_type = Kernel::getInstance()->em()
			->find('RZ\Renzo\Core\Entities\NodeType', (int)$node_type_id);

		if ($node_type !== null) {
			$this->assignation['node_type'] = $node_type;
			
			$form = $this->buildDeleteForm( $node_type );

			$form->handleRequest();

			if ($form->isValid() && 
				$form->getData()['node_type_id'] == $node_type->getId() ) {

		 		/*
		 		 * Delete All node-type association and schema
		 		 */
				$node_type->getHandler()->deleteWithAssociations();

				$msg = $this->getTranslator()->trans('node_type.deleted', array('%name%'=>$node_type->getName()));
		 		$request->getSession()->getFlashBag()->add('confirm', $msg);
	 			$this->getLogger()->info($msg);
		 		/*
		 		 * Redirect to update schema page
		 		 */
		 		$response = new RedirectResponse(
					Kernel::getInstance()->getUrlGenerator()->generate(
						'nodeTypesSchemaUpdate', 
						array(
							'_token' => static::$csrfProvider->generateCsrfToken(static::SCHEMA_TOKEN_INTENTION)
						)
					)
				);
				$response->prepare($request);

				return $response->send();
			}

			$this->assignation['form'] = $form->createView();

			return new Response(
				$this->getTwig()->render('node-types/delete.html.twig', $this->assignation),
				Response::HTTP_OK,
				array('content-type' => 'text/html')
			);
		}
		else {
			return $this->throw404();
		}
	}
	

	private function editNodeType( $data, NodeType $node_type)
	{
		foreach ($data as $key => $value) {
			if (isset($data['name'])) {
				throw new EntityAlreadyExistsException($this->getTranslator()->trans('node_type.cannot_rename_already_exists', array('%name%'=>$node_type->getName())), 1);
			}
			$setter = 'set'.ucwords($key);
			$node_type->$setter( $value );
		}
		Kernel::getInstance()->em()->flush();
		$node_type->getHandler()->updateSchema();
		return true;
	}

	private function addNodeType( $data, NodeType $node_type)
	{
		foreach ($data as $key => $value) {
			$setter = 'set'.ucwords($key);
			$node_type->$setter( $value );
		}

		$existing = Kernel::getInstance()->em()
			->getRepository('RZ\Renzo\Core\Entities\NodeType')
			->findOneBy(array('name'=>$node_type->getName()));
		if ($existing !== null) {
			throw new EntityAlreadyExistsException($this->getTranslator()->trans('node_type.already_exists', array('%name%'=>$node_type->getName())), 1);
		}
		
		Kernel::getInstance()->em()->persist($node_type);
		Kernel::getInstance()->em()->flush();
		$node_type->getHandler()->updateSchema();
		return true;
	}

	/**
	 * 
	 * @param  NodeType   $node_type 
	 * @return Symfony\Component\Form\Forms
	 */
	private function buildAddForm( NodeType $node_type )
	{
		$defaults = array(
			'name' =>           $node_type->getName(),
			'displayName' =>    $node_type->getDisplayName(),
			'description' =>    $node_type->getDescription(),
			'visible' =>        $node_type->isVisible(),
			'newsletterType' => $node_type->isNewsletterType(),
			'hidingNodes' =>    $node_type->isHidingNodes(),
		);
		$builder = $this->getFormFactory()
					->createBuilder('form', $defaults)
					->add('name', 'text', array(
						'constraints' => array(
							new NotBlank()
						)
					))
					->add('displayName',  'text', array(
						'constraints' => array(
							new NotBlank()
						)
					))
					->add('description',    'text', array('required' => false))
					->add('visible',        'checkbox', array('required' => false))
					->add('newsletterType', 'checkbox', array('required' => false))
					->add('hidingNodes',    'checkbox', array('required' => false))
		;

		return $builder->getForm();
	}
	/**
	 * 
	 * @param  NodeType   $node_type 
	 * @return Symfony\Component\Form\Forms
	 */
	private function buildEditForm( NodeType $node_type )
	{
		$defaults = array(
			'displayName' =>    $node_type->getDisplayName(),
			'description' =>    $node_type->getDescription(),
			'visible' =>        $node_type->isVisible(),
			'newsletterType' => $node_type->isNewsletterType(),
			'hidingNodes' =>    $node_type->isHidingNodes(),
		);
		$builder = $this->getFormFactory()
					->createBuilder('form', $defaults)
					->add('displayName',  'text', array(
						'constraints' => array(
							new NotBlank()
						)
					))
					->add('description',    'text', array('required' => false))
					->add('visible',        'checkbox', array('required' => false))
					->add('newsletterType', 'checkbox', array('required' => false))
					->add('hidingNodes',    'checkbox', array('required' => false))
		;

		return $builder->getForm();
	}
	/**
	 * 
	 * @param  NodeType   $node_type 
	 * @return Symfony\Component\Form\Forms
	 */
	private function buildDeleteForm( NodeType $node_type )
	{
		$builder = $this->getFormFactory()
			->createBuilder('form')
			->add('node_type_id', 'hidden', array(
				'data' => $node_type->getId(),
				'constraints' => array(
					new NotBlank()
				)
			))
		;

		return $builder->getForm();
	}


	public static function getNodeTypes()
	{
		return Kernel::getInstance()->em()
			->getRepository('RZ\Renzo\Core\Entities\NodeType')
			->findBy(array('newsletterType' => false));
	}
	public static function getNewsletterNodeTypes()
	{
		return Kernel::getInstance()->em()
			->getRepository('RZ\Renzo\Core\Entities\NodeType')
			->findBy(array('newsletterType' => true));
	}
}