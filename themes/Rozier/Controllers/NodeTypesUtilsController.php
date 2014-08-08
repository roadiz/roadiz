<?php 

namespace Themes\Rozier\Controllers;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Node;
use RZ\Renzo\Core\Entities\NodeType;
use RZ\Renzo\Core\Entities\NodeTypeField;
use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Core\Handlers\NodeTypeHandler;
use Themes\Rozier\RozierApp;

use RZ\Renzo\Core\Exceptions\EntityAlreadyExistsException;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;


class NodeTypesUtilsController extends 	RozierApp {

	/**
	 * Export a Json file containing NodeType datas and fields.
	 * @param  Symfony\Component\HttpFoundation\Request $request
	 * @param  int  $node_type_id
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function exportJsonFileAction(Request $request, $node_type_id) {
		$node_type = Kernel::getInstance()->em()
			->find('RZ\Renzo\Core\Entities\NodeType', (int)$node_type_id);

		$response =  new Response(
			$node_type->getHandler()->serializeToJson(),
			Response::HTTP_OK,
			array()
		);

		$response->headers->set('Content-Disposition', $response->headers->makeDisposition(
		    ResponseHeaderBag::DISPOSITION_ATTACHMENT,
		    $node_type->getName() . '.rzt')); // Rezo-Zero Type
		
		$response->prepare($request);

		return $response;		
	}


	// si existe pas -> import le node type + persist
	// else update sans persister 
	// PUIS flush
    // appel de fonction de deserialization
    // puis redirection vers node-types/list.html.twig

	/**
	 * Import a Json file (.rzt) containing NodeType datas and fields.
	 * @param  Symfony\Component\HttpFoundation\Request $request 
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function importJsonFileAction(Request $request) {
		$form = $this->buildImportJsonFileForm();
        
		$form->handleRequest();

        if ($form->isValid() && 
        	!empty($form->getData()['attachment'])) {
		    //$this->file->move($this->getUploadRootDir(), $this->file->getClientOriginalName());
		    //$this->path = $this->file->getClientOriginalName();
        	
			$serializedData = file_get_contents($form->getData()['attachment']['tmp_name']);

			if (null === json_decode($serializedData)) {
				$msg = $this->getTranslator()->trans('file.format.not_valid');
	 			$request->getSession()->getFlashBag()->add('error', $msg);
	 			$this->getLogger()->error($msg);

	 			// redirect even if its null
				$response = new RedirectResponse(
					Kernel::getInstance()->getUrlGenerator()->generate(
						'nodeTypesImportPage'
					)
				);
				$response->prepare($request);
				return $response->send();
			}
			else{
				$nodeType = NodeTypeHandler::deserializeFromJson($serializedData);

				

				$existingNT = Kernel::getInstance()->em()
										->getRepository('RZ\Renzo\Core\Entities\NodeType')
										->findOneBy(array('name'=>$nodeType->getName()));
				
				if (null === $existingNT ) {
					Kernel::getInstance()->em()->persist($nodeType);
				} 
				else {
					// Already exists, must update
					$existingNT->getHandler()->updateFromJson($nodeType);
				}

				Kernel::getInstance()->em()->flush();
				$nodeType->getHandler()->updateSchema();

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
        }

		$this->assignation['form'] = $form->createView();

		return new Response(
			$this->getTwig()->render('node-types/import.html.twig', $this->assignation),
			Response::HTTP_OK,
			array('content-type' => 'text/html')
		);	
    }


	/**
	 * @return Symfony\Component\Form\Forms
	 */
	private function buildImportJsonFileForm() {
	    $builder = $this->getFormFactory()
			->createBuilder('form')
	        ->add('attachment', 'file')
		;

		return $builder->getForm();
	}
}