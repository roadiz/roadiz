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
class NodeTypeFieldsController extends RozierApp
{
	/**
	 * List every node-type-fields
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function listAction( Request $request, $node_type_id )
	{
		$node_type = Kernel::getInstance()->em()
			->find('RZ\Renzo\Core\Entities\NodeType', (int)$node_type_id);

		if ($node_type !== null) {
			$fields = $node_type->getFields();

			$this->assignation['node_type'] = $node_type;
			$this->assignation['fields'] = $fields;

			return new Response(
				$this->getTwig()->render('node-type-fields/list.html.twig', $this->assignation),
				Response::HTTP_OK,
				array('content-type' => 'text/html')
			);
		}
		else {
			return $this->throw404();
		}
	}

	/**
	 * Return an edition form for requested node-type
	 * @param  integer $node_type_id        [description]
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function editAction( Request $request, $node_type_field_id )
	{
		$field = Kernel::getInstance()->em()
			->find('RZ\Renzo\Core\Entities\NodeTypeField', (int)$node_type_field_id);

		if ($field !== null) {

			$this->assignation['node_type'] = $field->getNodeType();
			$this->assignation['field'] = $field;
			
			$form = $this->buildEditForm( $field );

			$form->handleRequest();

			if ($form->isValid()) {
		 		$this->editNodeTypeField($form->getData(), $field);

		 		$msg = $this->getTranslator()->trans('node_type_field.updated', array('%name%'=>$field->getName()));
		 		$request->getSession()->getFlashBag()->add('confirm', $msg);
	 			$this->getLogger()->info($msg);
		 		/*
		 		 * Force redirect to avoid resending form when refreshing page
		 		 */
		 		$response = new RedirectResponse(
					Kernel::getInstance()->getUrlGenerator()->generate(
						'nodeTypeFieldsEditPage',
						array('node_type_field_id' => $field->getId())
					)
				);
				$response->prepare($request);

				return $response->send();
			}

			$this->assignation['form'] = $form->createView();

			return new Response(
				$this->getTwig()->render('node-type-fields/edit.html.twig', $this->assignation),
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
	public function addAction( Request $request, $node_type_id )
	{
		$field = new NodeTypeField();
		$node_type = Kernel::getInstance()->em()
			->find('RZ\Renzo\Core\Entities\NodeType', (int)$node_type_id);

		if ($node_type !== null && 
			$field !== null) {

			$this->assignation['node_type'] = $node_type;
			$this->assignation['field'] = $field;
			$form = $this->buildEditForm( $field );

			$form->handleRequest();

			if ($form->isValid()) {
		 		$this->addNodeTypeField($form->getData(), $field, $node_type);

		 		$msg = $this->getTranslator()->trans('node_type_field.created', array('%name%'=>$field->getName()));
		 		$request->getSession()->getFlashBag()->add('confirm', $msg);
	 			$this->getLogger()->info($msg);
		 		/*
		 		 * Force redirect to avoid resending form when refreshing page
		 		 */
		 		$response = new RedirectResponse(
					Kernel::getInstance()->getUrlGenerator()->generate(
						'nodeTypeFieldsListPage',
						array('node_type_id' => $field->getNodeType()->getId())
					)
				);
				$response->prepare($request);

				return $response->send();
			}

			$this->assignation['form'] = $form->createView();

			return new Response(
				$this->getTwig()->render('node-type-fields/add.html.twig', $this->assignation),
				Response::HTTP_OK,
				array('content-type' => 'text/html')
			);
		}
		else {
			return $this->throw404();
		}
	}

	/**
	 * Return an deletion form for requested node
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function deleteAction( Request $request, $node_type_field_id )
	{
		$field = Kernel::getInstance()->em()
			->find('RZ\Renzo\Core\Entities\NodeTypeField', (int)$node_type_field_id);

		if ($field !== null) {
			$this->assignation['field'] = $field;
			
			$form = $this->buildDeleteForm( $field );

			$form->handleRequest();

			if ($form->isValid() && 
				$form->getData()['node_type_field_id'] == $field->getId() ) {

				$nodeTypeId = $field->getNodeType()->getId();

		 		Kernel::getInstance()->em()->remove($field);
		 		Kernel::getInstance()->em()->flush();

		 		/*
		 		 * Update Database
		 		 */
		 		$nodeType = Kernel::getInstance()->em()
					->find('RZ\Renzo\Core\Entities\NodeType', (int)$nodeTypeId);

		 		$nodeType->getHandler()->updateSchema();

		 		$msg = $this->getTranslator()->trans('node_type_field.deleted', array('%name%'=>$field->getName()));
		 		$request->getSession()->getFlashBag()->add('confirm', $msg);
	 			$this->getLogger()->info($msg);

		 		/*
		 		 * Force redirect to avoid resending form when refreshing page
		 		 */
		 		$response = new RedirectResponse(
					Kernel::getInstance()->getUrlGenerator()->generate(
						'nodeTypeFieldsListPage', 
						array('node_type_id' => $nodeTypeId)
					)
				);
				$response->prepare($request);

				return $response->send();
			}

			$this->assignation['form'] = $form->createView();

			return new Response(
				$this->getTwig()->render('node-type-fields/delete.html.twig', $this->assignation),
				Response::HTTP_OK,
				array('content-type' => 'text/html')
			);
		}
		else {
			return $this->throw404();
		}
	}
	private function editNodeTypeField( $data, NodeTypeField $field)
	{
		foreach ($data as $key => $value) {
			$setter = 'set'.ucwords($key);
			$field->$setter( $value );
		}
		Kernel::getInstance()->em()->flush();
		
		$field->getNodeType()->getHandler()->updateSchema();

	}
	private function addNodeTypeField( $data, NodeTypeField $field, NodeType $node_type)
	{
		foreach ($data as $key => $value) {
			$setter = 'set'.ucwords($key);
			$field->$setter( $value );
		}

		$field->setNodeType( $node_type );

		Kernel::getInstance()->em()->persist($field);
		Kernel::getInstance()->em()->flush();

		$node_type->getHandler()->updateSchema();
	}


	/**
	 * @param  NodeTypeField   $field 
	 * @return Symfony\Component\Form\Forms
	 */
	private function buildEditForm( NodeTypeField $field )
	{
		$defaults = array(
			'name' =>           $field->getName(),
			'label' =>    		$field->getLabel(),
			'type' =>    		$field->getType(),
			'description' =>    $field->getDescription(),
			'visible' =>        $field->isVisible(),
			'indexed' => 		$field->isIndexed(),
		);
		$builder = $this->getFormFactory()
					->createBuilder('form', $defaults)
					->add('name', 'text', array(
						'constraints' => array(
							new NotBlank()
						)
					))
					->add('label',  'text', array(
						'constraints' => array(
							new NotBlank()
						)
					))
					->add('type', 'choice', array(
						'required' => true,
						'choices' => NodeTypeField::$typeToHuman
					))
					->add('description',    'text', array('required' => false))
					->add('visible',  'checkbox', array('required' => false))
					->add('indexed', 'checkbox', array('required' => false))
		;

		return $builder->getForm();
	}
	/**
	 * 
	 * @param  NodeTypeField   $node 
	 * @return Symfony\Component\Form\Forms
	 */
	private function buildDeleteForm( NodeTypeField $field )
	{
		$builder = $this->getFormFactory()
			->createBuilder('form')
			->add('node_type_field_id', 'hidden', array(
				'data' => $field->getId(),
				'constraints' => array(
					new NotBlank()
				)
			))
		;

		return $builder->getForm();
	}
}