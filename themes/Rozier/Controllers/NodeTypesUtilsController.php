<?php 

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

	/**
	 * Import a Json file (.rzt) containing NodeType datas and fields.
	 * @param  Symfony\Component\HttpFoundation\Request $request 
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function importJsonFileAction(Request $request) {
		$form = $this->buildImportJsonFileForm();
        
        if ($form->isValid()) {
        	$this->importJsonFile();

			// si existe pas -> import le node type + persist
			// else update sans persister 
			// PUIS flush
            // appel de fonction de deserialization
            // puis redirection vers node-types/list.html.twig
            //
        }

		$this->assignation['form'] = $form->createView();

		return new Response(
			$this->getTwig()->render('node-types/import.html.twig', $this->assignation),
			Response::HTTP_OK,
			array('content-type' => 'text/html')
		);	
    }



    /**
     * Import Node Type datas from a .rzt file.
     * @param  string  $url
     * @return OBJET NODE TYPE A PERSISTER
     *//*
    public function importJsonFile() {
        if (null === $this->file) {
            return;
        }

        $this->file->move($this->getUploadRootDir(), $this->path);

        unset($this->file);
    }*/

    /**
     * Update an existing Node Type.
     * @param string  $url
     * @return bool
     */
    public function updateFromJson($json_string) {
    
    }

	/**
	 * @return Symfony\Component\Form\Forms
	 */
	private function buildImportJsonFileForm() {
	    $builder = $this->getFormFactory()
			->createBuilder('form')
	        ->add('name')
	        ->add('file', 'file')
		;

		return $builder->getForm();
	}
}