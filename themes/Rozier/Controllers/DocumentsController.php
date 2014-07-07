<?php 

namespace Themes\Rozier\Controllers;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Document;
use RZ\Renzo\Core\Entities\Translation;

use Themes\Rozier\RozierApp;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

class DocumentsController extends RozierApp {

	public function indexAction() {

		$documents = Kernel::getInstance()->em()
			->getRepository('RZ\Renzo\Core\Entities\Document')
			->findAll();

		$this->assignation['documents'] = $documents;

		return new Response(
			$this->getTwig()->render('documents/list.html.twig', $this->assignation),
			Response::HTTP_OK,
			array('content-type' => 'text/html')
		);
	}


	public function uploadAction()
	{

		/*
		 * Handle main form
		 */
		$form = $this->buildUploadForm();
		$form->handleRequest();

		if ($form->isValid()) {

	 		if ($this->uploadDocument( $form ) === true) {

	 			$response = new Response();
	 			$response->setContent(json_encode(array(
	 			    'success' => true,
	 			)));
	 			$response->headers->set('Content-Type', 'application/json');
	 			$response->setStatusCode(200);
	 			$response->prepare(Kernel::getInstance()->getRequest());
				return $response->send();
	 		}
	 		else {
	 			$response = new Response();
	 			$response->setContent(json_encode(array(
	 			    "error" => "File could not be saved"
	 			)));
	 			$response->headers->set('Content-Type', 'application/json');
	 			$response->setStatusCode(400);
	 			$response->prepare(Kernel::getInstance()->getRequest());
				return $response->send();
	 		}
		}
		$this->assignation['form'] = $form->createView();
		$this->assignation['maxUploadSize'] = \Symfony\Component\HttpFoundation\File\UploadedFile::getMaxFilesize()  / 1024 / 1024;
 
		return new Response(
			$this->getTwig()->render('documents/upload.html.twig', $this->assignation),
			Response::HTTP_OK,
			array('content-type' => 'text/html')
		);
	}

	private function buildUploadForm()
	{
		$builder = $this->getFormFactory()
					->createBuilder('form')
					->add('attachment', 'file');

		return $builder->getForm();
	}

	/**
	 * Handle upload form data to create a Document
	 * @param  array $data
	 * @return void
	 */
	private function uploadDocument( $data )
	{
		if (!empty($data['attachment'])) {

			$file = $data['attachment']->getData();

			$uploadedFile = new \Symfony\Component\HttpFoundation\File\UploadedFile( 
				$file['tmp_name'],
				$file['name'],
				$file['type'],
				$file['size'],
				$file['error']
			);

			if ($uploadedFile !== null && 
				$uploadedFile->getError() == UPLOAD_ERR_OK && 
				$uploadedFile->isValid()) {

				try {

					$document = new Document();
					$document->setFilename($uploadedFile->getClientOriginalName());
					$document->setMimeType($uploadedFile->getMimeType());

					Kernel::getInstance()->em()->persist($document);
					Kernel::getInstance()->em()->flush();

					$uploadedFile->move(Document::getFilesFolder().'/'.$document->getFolder(), $document->getFilename());

					$this->getSession()->getFlashBag()->add('confirm', 'Document “'.$document->getFilename().'” has been uploaded.');
					return true;
				}
				catch(\Exception $e){
					$this->getSession()->getFlashBag()->add('error', 'Document cannot be persisted.');
					return false;
				}
			}
		}
		$this->getSession()->getFlashBag()->add('error', 'Document cannot be uploaded.');
		return false;
	}
}