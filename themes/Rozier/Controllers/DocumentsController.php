<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file DocumentsController.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Controllers;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Document;
use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Core\ListManagers\EntityListManager;
use RZ\Renzo\Core\Utils\SplashbasePictureFinder;
use Themes\Rozier\RozierApp;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

/**
 * {@inheritdoc}
 */
class DocumentsController extends RozierApp
{
    /**
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_DOCUMENTS');
        /*
         * Manage get request to filter list
         */
        $listManager = new EntityListManager(
            $request,
            $this->getService('em'),
            'RZ\Renzo\Core\Entities\Document',
            array(),
            array('createdAt'=> 'DESC')
        );
        $listManager->setItemPerPage(28);
        $listManager->handle();

        $this->assignation['filters'] = $listManager->getAssignation();
        $this->assignation['documents'] = $listManager->getEntities();

        $this->assignation['thumbnailFormat'] = array(
            'width' => 128,
            'quality' => 50,
            'crop' => '1x1'
        );

        return new Response(
            $this->getTwig()->render('documents/list.html.twig', $this->assignation),
            Response::HTTP_OK,
            array('content-type' => 'text/html')
        );
    }

    /**
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $documentId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, $documentId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_DOCUMENTS');

        $document = $this->getService('em')
            ->find('RZ\Renzo\Core\Entities\Document', (int) $documentId);

        if ($document !== null) {

            $this->assignation['document'] = $document;

            /*
             * Handle main form
             */
            $form = $this->buildEditForm($document);
            $form->handleRequest();

            if ($form->isValid()) {

                $this->editDocument($form->getData(), $document);
                $msg = $this->getTranslator()->trans('document.%name%.updated', array(
                    '%name%'=>$document->getFilename()
                ));
                $request->getSession()->getFlashBag()->add('confirm', $msg);
                $this->getService('logger')->info($msg);
                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                $response = new RedirectResponse(
                    $this->getService('urlGenerator')->generate(
                        'documentsEditPage',
                        array('documentId' => $document->getId())
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
        } else {
            return $this->throw404();
        }
    }

    /**
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $documentId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function previewAction(Request $request, $documentId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_DOCUMENTS');

        $document = $this->getService('em')
            ->find('RZ\Renzo\Core\Entities\Document', (int) $documentId);

        if ($document !== null) {

            $this->assignation['document'] = $document;
            $this->assignation['thumbnailFormat'] = array(
                'width' => 500,
                'quality' => 70
            );

            return new Response(
                $this->getTwig()->render('documents/preview.html.twig', $this->assignation),
                Response::HTTP_OK,
                array('content-type' => 'text/html')
            );
        } else {
            return $this->throw404();
        }
    }

    /**
     * Return an deletion form for requested document.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $documentId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Request $request, $documentId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_DOCUMENTS_DELETE');

        $document = $this->getService('em')
            ->find('RZ\Renzo\Core\Entities\Document', (int) $documentId);

        if ($document !== null) {
            $this->assignation['document'] = $document;
            $form = $this->buildDeleteForm($document);
            $form->handleRequest();

            if ($form->isValid() &&
                $form->getData()['documentId'] == $document->getId()) {

                try {
                    $document->getHandler()->removeWithAssets();
                    $msg = $this->getTranslator()->trans('document.%name%.deleted', array('%name%'=>$document->getFilename()));
                    $request->getSession()->getFlashBag()->add('confirm', $msg);
                    $this->getService('logger')->info($msg);

                } catch (\Exception $e) {

                    $msg = $this->getTranslator()->trans('document.%name%.cannot_delete', array('%name%'=>$document->getFilename()));
                    $request->getSession()->getFlashBag()->add('error', $msg);
                    $this->getService('logger')->warning($msg);
                }
                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                $response = new RedirectResponse(
                    $this->getService('urlGenerator')->generate('documentsHomePage')
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
        } else {
            return $this->throw404();
        }
    }

    /**
     * Embed external document page.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function embedAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_DOCUMENTS');

        /*
         * Handle main form
         */
        $form = $this->buildEmbedForm();
        $form->handleRequest();

        if ($form->isValid()) {

            try {
                $document = $this->embedDocument($form->getData());

                $msg = $this->getTranslator()->trans('document.%name%.uploaded', array(
                    '%name%'=>$document->getName()
                ));
                $request->getSession()->getFlashBag()->add('confirm', $msg);
                $this->getService('logger')->info($msg);

            } catch (\Exception $e) {
                $request->getSession()->getFlashBag()->add('error', $e->getMessage());
                $this->getService('logger')->error($e->getMessage());
            }
            /*
             * Force redirect to avoid resending form when refreshing page
             */
            $response = new RedirectResponse(
                $this->getService('urlGenerator')->generate('documentsHomePage')
            );
            $response->prepare($request);

            return $response->send();
        }


        $this->assignation['form'] = $form->createView();

        return new Response(
            $this->getTwig()->render('documents/embed.html.twig', $this->assignation),
            Response::HTTP_OK,
            array('content-type' => 'text/html')
        );
    }

    /**
     * Get random external document page.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function randomAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_DOCUMENTS');

        try {
            $document = $this->randomDocument();

            $msg = $this->getTranslator()->trans('document.%name%.uploaded', array(
                '%name%'=>$document->getFilename()
            ));
            $request->getSession()->getFlashBag()->add('confirm', $msg);
            $this->getService('logger')->info($msg);

        } catch (\Exception $e) {
            $request->getSession()->getFlashBag()->add('error', $e->getMessage());
            $this->getService('logger')->error($e->getMessage());
        }
        /*
         * Force redirect to avoid resending form when refreshing page
         */
        $response = new RedirectResponse(
            $this->getService('urlGenerator')->generate('documentsHomePage')
        );
        $response->prepare($request);

        return $response->send();
    }

    /**
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function uploadAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_DOCUMENTS');

        /*
         * Handle main form
         */
        $form = $this->buildUploadForm();
        $form->handleRequest();

        if ($form->isValid()) {

            if (false !== $document = $this->uploadDocument($form)) {

                $msg = $this->getTranslator()->trans('document.%name%.uploaded', array(
                    '%name%'=>$document->getFilename()
                ));
                $request->getSession()->getFlashBag()->add('confirm', $msg);
                $this->getService('logger')->info($msg);

                $response = new Response();
                $response->setContent(json_encode(array(
                    'success' => true,
                )));
                $response->headers->set('Content-Type', 'application/json');
                $response->setStatusCode(200);
                $response->prepare($request);

                return $response->send();

            } else {
                $msg = $this->getTranslator()->trans('document.cannot_persist');
                $request->getSession()->getFlashBag()->add('error', $msg);
                $this->getService('logger')->error($msg);

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
     * Return a node list using this document.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $documentId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function usageAction(Request $request, $documentId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_DOCUMENTS');

        $document = $this->getService('em')
            ->find('RZ\Renzo\Core\Entities\Document', (int) $documentId);

        if ($document !== null) {
            $this->assignation['document'] = $document;
            $this->assignation['usages'] = $document->getNodesSourcesByFields();

            return new Response(
                $this->getTwig()->render('documents/usage.html.twig', $this->assignation),
                Response::HTTP_OK,
                array('content-type' => 'text/html')
            );
        } else {
            return $this->throw404();
        }
    }

    /**
     * @param RZ\Renzo\Core\Entities\Document $doc
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildDeleteForm(Document $doc)
    {
        $defaults = array(
            'documentId' => $doc->getId()
        );
        $builder = $this->getService('formFactory')
                    ->createBuilder('form', $defaults)
                    ->add('documentId', 'hidden', array(
                        'data' => $doc->getId(),
                        'constraints' => array(
                            new NotBlank()
                        )
                    ));

        return $builder->getForm();
    }
    /**
     * @param RZ\Renzo\Core\Entities\Document $document
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildEditForm(Document $document)
    {
        $defaults = array(
            'private' => $document->isPrivate(),
            'name' => $document->getName(),
            'description' => $document->getDescription(),
            'copyright' => $document->getCopyright(),
            'filename' => $document->getFilename()
        );

        $builder = $this->getService('formFactory')
                    ->createBuilder('form', $defaults)
                    ->add('name', 'text', array(
                        'label' => $this->getTranslator()->trans('name'),
                        'required' => false
                    ))
                    ->add('filename', 'text', array(
                        'label' => $this->getTranslator()->trans('filename'),
                        'required' => false
                    ))
                    ->add('description', new \RZ\Renzo\CMS\Forms\MarkdownType(), array(
                        'label' => $this->getTranslator()->trans('description'),
                        'required' => false
                    ))
                    ->add('copyright', 'text', array(
                        'label' => $this->getTranslator()->trans('copyright'),
                        'required' => false
                    ))
                    ->add('private', 'checkbox', array(
                        'label' => $this->getTranslator()->trans('private'),
                        'required' => false
                    ));

        return $builder->getForm();
    }

    /**
     * @return Symfony\Component\Form\Form
     */
    private function buildUploadForm()
    {
        $builder = $this->getService('formFactory')
                    ->createBuilder('form')
                    ->add('attachment', 'file', array(
                        'label' => $this->getTranslator()->trans('choose.documents.to_upload')
                    ));

        return $builder->getForm();
    }

    /**
     * @return Symfony\Component\Form\Form
     */
    private function buildEmbedForm()
    {
        $services = array();
        foreach (array_keys($this->getService('document.platforms')) as $value) {
            $services[$value] = ucwords($value);
        }

        $builder = $this->getService('formFactory')
                    ->createBuilder('form')
                    ->add('embedId', 'text', array(
                        'label' => $this->getTranslator()->trans('document.embedId')
                    ))
                    ->add('embedPlatform', 'choice', array(
                        'label' => $this->getTranslator()->trans('document.platform'),
                        'choices' => $services
                    ));

        return $builder->getForm();
    }

    private function embedDocument($data)
    {
        $handlers = $this->getService('document.platforms');

        if (isset($data['embedId']) &&
            isset($data['embedPlatform']) &&
            in_array($data['embedPlatform'], array_keys($handlers))) {

            $class = $handlers[$data['embedPlatform']];
            $finder = new $class($data['embedId']);

            if ($finder->exists()) {
                return $finder->createDocumentFromFeed($this->getService());

            } else {
                 throw new \RuntimeException("embedId.does_not_exist", 1);
            }

        } else {
            throw new \RuntimeException("bad.request", 1);
        }
    }
    /**
     * Download a random document.
     *
     * @return RZ\Renzo\Core\Entities\Document
     */
    public function randomDocument()
    {
        $finder = new SplashbasePictureFinder();
        return $finder->createDocumentFromFeed($this->getService());
    }

    /**
     * @param array                           $data
     * @param RZ\Renzo\Core\Entities\Document $document
     */
    private function editDocument($data, Document $document)
    {
        if (!empty($data['filename']) &&
            $data['filename'] != $document->getFilename()) {

            $oldUrl = $document->getAbsolutePath();

            /*
             * If file exists, just rename it
             */
            // set filename to clean given string before renaming file.
            $document->setFilename($data['filename']);
            rename(
                $oldUrl,
                $document->getAbsolutePath()
            );

            unset($data['filename']);
        }

        foreach ($data as $key => $value) {
            $setter = 'set'.ucwords($key);
            $document->$setter($value);
        }

        $this->getService('em')->flush();
    }

    /**
     * Handle upload form data to create a Document.
     *
     * @param Symfony\Component\Form\Form $data
     *
     * @return boolean
     */
    private function uploadDocument($data)
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

                    $this->getService('em')->persist($document);
                    $this->getService('em')->flush();

                    $uploadedFile->move(Document::getFilesFolder().'/'.$document->getFolder(), $document->getFilename());

                    return $document;
                } catch (\Exception $e) {

                    return false;
                }
            }
        }

        return false;
    }
}
