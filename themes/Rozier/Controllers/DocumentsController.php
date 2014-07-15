<?php 

namespace Themes\Rozier\Controllers;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Document;
use RZ\Renzo\Core\Entities\Translation;

use Themes\Rozier\RozierApp;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

class DocumentsController extends RozierApp {

	public function indexAction( Request $request ) {

		$documents = Kernel::getInstance()->em()
			->getRepository('RZ\Renzo\Core\Entities\Document')
			->findAll();

		$this->assignation['documents'] = $documents;
		$this->assignation['thumbnailFormat'] = array(
			'width' => 100,
			'quality' => 50,
			'crop' => '3x2'
		);

		return new Response(
			$this->getTwig()->render('documents/list.html.twig', $this->assignation),
			Response::HTTP_OK,
			array('content-type' => 'text/html')
		);
	}

	public function editAction( Request $request, $document_id )
	{
		$document = Kernel::getInstance()->em()
			->find('RZ\Renzo\Core\Entities\Document', (int)$document_id);

		if ($document !== null) {
			
			$this->assignation['document'] = $document;
			$this->assignation['thumbnailFormat'] = array(
				'width' => 500,
				'quality' => 70
			);

			/*
			 * Handle main form
			 */
			$form = $this->buildEditForm( $document );
			$form->handleRequest();

			if ($form->isValid()) {

				$this->editDocument( $form->getData(), $document );
				$msg = $this->getTranslator()->trans('document.updated', array(
		 			'%name%'=>$document->getFilename()
		 		));
				$request->getSession()->getFlashBag()->add('confirm', $msg);
	 			$this->getLogger()->info($msg);
				/*
		 		 * Force redirect to avoid resending form when refreshing page
		 		 */
		 		$response = new RedirectResponse(
					Kernel::getInstance()->getUrlGenerator()->generate(
						'documentsEditPage',
						array('document_id' => $document->getId())
					)
				);
				$response->prepare($request);

				return $response->send();
			}

			$this->assignation['form'] = $form->createView();

			return new Response(
				$this->getTwig()->render('documents/edit.html.twig', $this->assignation),
				Response::HTTP_OK,
				array('content-type' => 'text/html')
			);
		}
		else {
			return $this->throw404();
		}
	}
	/**
	 * Return an deletion form for requested document
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function deleteAction( Request $request, $document_id )
	{
		$document = Kernel::getInstance()->em()
			->find('RZ\Renzo\Core\Entities\Document', (int)$document_id);

		if ($document !== null) {
			$this->assignation['document'] = $document;
			
			$form = $this->buildDeleteForm( $document );
			$form->handleRequest();

			if ($form->isValid() && 
				$form->getData()['document_id'] == $document->getId() ) {

				try{
					$document->getHandler()->removeWithAssets();
					$msg = $this->getTranslator()->trans('document.deleted', array('%name%'=>$document->getFilename()));
					$request->getSession()->getFlashBag()->add('confirm', $msg);
		 			$this->getLogger()->info($msg);
				}
				catch(\Exception $e){
					$msg = $this->getTranslator()->trans('document.cannot_delete', array('%name%'=>$document->getFilename()));
					$request->getSession()->getFlashBag()->add('error', $msg);
		 			$this->getLogger()->warning($msg);
				}
		 		/*
		 		 * Force redirect to avoid resending form when refreshing page
		 		 */
		 		$response = new RedirectResponse(
					Kernel::getInstance()->getUrlGenerator()->generate('documentsHomePage')
				);
				$response->prepare($request);

				return $response->send();
			}

			$this->assignation['form'] = $form->createView();

			return new Response(
				$this->getTwig()->render('documents/delete.html.twig', $this->assignation),
				Response::HTTP_OK,
				array('content-type' => 'text/html')
			);
		}
		else {
			return $this->throw404();
		}
	}

	public function uploadAction( Request $request )
	{
		/*
		 * Handle main form
		 */
		$form = $this->buildUploadForm();
		$form->handleRequest();

		if ($form->isValid()) {

	 		if (false !== $document = $this->uploadDocument( $form )) {

	 			$msg = $this->getTranslator()->trans('document.uploaded', array(
		 			'%name%'=>$document->getFilename()
		 		));
	 			$request->getSession()->getFlashBag()->add('confirm', $msg);
	 			$this->getLogger()->info($msg);

	 			$response = new Response();
	 			$response->setContent(json_encode(array(
	 			    'success' => true,
	 			)));
	 			$response->headers->set('Content-Type', 'application/json');
	 			$response->setStatusCode(200);
	 			$response->prepare($request);
				return $response->send();
	 		}
	 		else {
	 			$msg = $this->getTranslator()->trans('document.cannot_persist');
	 			$request->getSession()->getFlashBag()->add('error', $msg);
	 			$this->getLogger()->error($msg);

	 			$response = new Response();
	 			$response->setContent(json_encode(array(
	 			    "error" => $this->getTranslator()->trans('document.cannot_persist')
	 			)));
	 			$response->headers->set('Content-Type', 'application/json');
	 			$response->setStatusCode(400);
	 			$response->prepare($request);
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

	/**
	 * 
	 * @param  Document $ua
	 * @return Symfony\Component\Form\Forms
	 */
	private function buildDeleteForm( Document $doc )
	{
		$defaults = array(
			'document_id' =>  $doc->getId()
		);
		$builder = $this->getFormFactory()
					->createBuilder('form', $defaults)
					->add('document_id', 'hidden', array(
						'data' => $doc->getId(),
						'constraints' => array(
							new NotBlank()
						)
					));

		return $builder->getForm();
	}
	/**
	 * 
	 * @param  Document   $document 
	 * @return Symfony\Component\Form\Forms
	 */
	private function buildEditForm( Document $document )
	{
		$defaults = array(
			'private' => $document->isPrivate(),
			'name' => $document->getName(),
			'description' => $document->getDescription(),
			'copyright' => $document->getCopyright(),
		);

		$builder = $this->getFormFactory()
					->createBuilder('form', $defaults)
					->add('name', 'text', array('required' => false))
					->add('description', new \RZ\Renzo\CMS\Forms\MarkdownType(), array('required' => false))
					->add('copyright', 'text', array('required' => false))
					->add('private', 'checkbox', array('required' => false))
		;

		return $builder->getForm();
	}

	private function buildUploadForm()
	{
		$builder = $this->getFormFactory()
					->createBuilder('form')
					->add('attachment', 'file');

		return $builder->getForm();
	}


	private function editDocument( $data, Document $document)
	{
		foreach ($data as $key => $value) {
			$setter = 'set'.ucwords($key);
			$document->$setter( $value );
		}

		Kernel::getInstance()->em()->flush();
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
					return $document;
				}
				catch(\Exception $e){
					
					return false;
				}
			}
		}
		return false;
	}
}