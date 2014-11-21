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

use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Entities\Folder;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\ListManagers\EntityListManager;
use RZ\Roadiz\Core\Utils\SplashbasePictureFinder;
use Themes\Rozier\RozierApp;

use Themes\Rozier\AjaxControllers\AjaxDocumentsExplorerController;

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
    public function indexAction(Request $request, $folderId = null)
    {
        $this->validateAccessForRole('ROLE_ACCESS_DOCUMENTS');

        $prefilters = array();

        if (null !== $folderId &&
            $folderId > 0) {

            $folder = $this->getService('em')
                           ->find('RZ\Roadiz\Core\Entities\Folder', (int) $folderId);

            $prefilters['folders'] = array($folder);
            $this->assignation['folder'] = $folder;
        }

        /*
         * Handle bulk folder form
         */
        $joinFolderForm = $this->buildLinkFoldersForm();
        $joinFolderForm->handleRequest();
        if ($joinFolderForm->isValid()) {

            $data = $joinFolderForm->getData();

            if ($joinFolderForm->get('submitFolder')->isClicked()) {
                $msg = $this->joinFolder($data);
            } elseif ($joinFolderForm->get('submitUnfolder')->isClicked()) {
                $msg = $this->leaveFolder($data);
            } else {
                $msg = $this->getTranslator()->trans('wrong.request');
            }

            $request->getSession()->getFlashBag()->add('confirm', $msg);
            $this->getService('logger')->info($msg);

            $response = new RedirectResponse(
                $this->getService('urlGenerator')->generate(
                    'documentsHomePage',
                    array('folderId' => $folderId)
                )
            );
            $response->prepare($request);

            return $response->send();
        }
        $this->assignation['joinFolderForm'] = $joinFolderForm->createView();

        /*
         * Manage get request to filter list
         */
        $listManager = new EntityListManager(
            $request,
            $this->getService('em'),
            'RZ\Roadiz\Core\Entities\Document',
            $prefilters,
            array('createdAt'=> 'DESC')
        );
        $listManager->setItemPerPage(28);
        $listManager->handle();

        $this->assignation['filters'] = $listManager->getAssignation();
        $this->assignation['documents'] = $listManager->getEntities();

        $this->assignation['thumbnailFormat'] = array(
            'width' =>   128,
            'quality' => 50,
            'crop' =>    '1x1'
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
            ->find('RZ\Roadiz\Core\Entities\Document', (int) $documentId);

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
            ->find('RZ\Roadiz\Core\Entities\Document', (int) $documentId);

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
            ->find('RZ\Roadiz\Core\Entities\Document', (int) $documentId);

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
     * Return an deletion form for multiple docs.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function bulkDeleteAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_DOCUMENTS_DELETE');

        $documentsIds = $request->get('documents');

        $documents = $this->getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\Document')
            ->findBy(array(
                'id' => $documentsIds
            ));

        if ($documents !== null &&
            count($documents) > 0) {

            $this->assignation['documents'] = $documents;
            $form = $this->buildBulkDeleteForm($documentsIds);

            $form->handleRequest();

            if ($form->isValid()) {

                try {

                    foreach ($documents as $document) {
                        $document->getHandler()->removeWithAssets();
                        $msg = $this->getTranslator()->trans('document.%name%.deleted', array('%name%'=>$document->getFilename()));
                        $request->getSession()->getFlashBag()->add('confirm', $msg);
                        $this->getService('logger')->info($msg);
                    }

                } catch (\Exception $e) {

                    $msg = $this->getTranslator()->trans('documents.cannot_delete');
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
            $this->assignation['action'] = '?'. http_build_query(array('documents'=>$documentsIds));
            $this->assignation['thumbnailFormat'] = array(
                'width' =>   128,
                'quality' => 50,
                'crop' =>    '1x1'
            );

            return new Response(
                $this->getTwig()->render('documents/bulkDelete.html.twig', $this->assignation),
                Response::HTTP_OK,
                array('content-type' => 'text/html')
            );
        } else {
            return $this->throw404();
        }
    }


    /**
     * Return an deletion form for multiple docs.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function bulkDownloadAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_DOCUMENTS');

        $documentsIds = $request->get('documents');

        $documents = $this->getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\Document')
            ->findBy(array(
                'id' => $documentsIds
            ));

        if ($documents !== null &&
            count($documents) > 0) {

            $this->assignation['documents'] = $documents;
            $form = $this->buildBulkDownloadForm($documentsIds);

            $form->handleRequest();

            if ($form->isValid()) {

                try {

                    return $this->downloadDocuments($documents);

                } catch (\Exception $e) {

                    $msg = $this->getTranslator()->trans('documents.cannot_download');
                    $request->getSession()->getFlashBag()->add('error', $msg);
                    $this->getService('logger')->warning($msg);

                    /*
                     * Force redirect to avoid resending form when refreshing page
                     */
                    $response = new RedirectResponse(
                        $this->getService('urlGenerator')->generate('documentsHomePage')
                    );
                    $response->prepare($request);

                    return $response->send();
                }
            }

            $this->assignation['form'] = $form->createView();
            $this->assignation['action'] = '?'. http_build_query(array('documents'=>$documentsIds));
            $this->assignation['thumbnailFormat'] = array(
                'width' =>   128,
                'quality' => 50,
                'crop' =>    '1x1'
            );

            return new Response(
                $this->getTwig()->render('documents/bulkDownload.html.twig', $this->assignation),
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
    public function embedAction(Request $request, $folderId = null)
    {
        $this->validateAccessForRole('ROLE_ACCESS_DOCUMENTS');

        if (null !== $folderId &&
            $folderId > 0) {

            $folder = $this->getService('em')
                           ->find('RZ\Roadiz\Core\Entities\Folder', (int) $folderId);

            $prefilters['folders'] = array($folder);
            $this->assignation['folder'] = $folder;
        }

        /*
         * Handle main form
         */
        $form = $this->buildEmbedForm();
        $form->handleRequest();

        if ($form->isValid()) {

            try {
                $document = $this->embedDocument($form->getData(), $folderId);

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
                $this->getService('urlGenerator')->generate('documentsHomePage', array('folderId'=>$folderId))
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
    public function randomAction(Request $request, $folderId = null)
    {
        $this->validateAccessForRole('ROLE_ACCESS_DOCUMENTS');

        try {
            $document = $this->randomDocument($folderId);

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
            $this->getService('urlGenerator')->generate('documentsHomePage', array('folderId'=>$folderId))
        );
        $response->prepare($request);

        return $response->send();
    }

    /**
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function uploadAction(Request $request, $folderId = null)
    {
        $this->validateAccessForRole('ROLE_ACCESS_DOCUMENTS');

        if (null !== $folderId &&
            $folderId > 0) {

            $folder = $this->getService('em')
                           ->find('RZ\Roadiz\Core\Entities\Folder', (int) $folderId);

            $prefilters['folders'] = array($folder);
            $this->assignation['folder'] = $folder;
        }

        /*
         * Handle main form
         */
        $form = $this->buildUploadForm($folderId);
        $form->handleRequest();

        if ($form->isValid()) {

            $document = $this->uploadDocument($form, $folderId);

            if (false !== $document) {

                $msg = $this->getTranslator()->trans('document.%name%.uploaded', array(
                    '%name%'=>$document->getFilename()
                ));
                $request->getSession()->getFlashBag()->add('confirm', $msg);
                $this->getService('logger')->info($msg);

                return new Response(
                    json_encode(array(
                        'success' => true,
                        'documentId' => $document->getId(),
                        'thumbnail' => array(
                            'id' => $document->getId(),
                            'filename'=>$document->getFilename(),
                            'thumbnail' => $document->getViewer()->getDocumentUrlByArray(AjaxDocumentsExplorerController::$thumbnailArray),
                            'html' => $this->getTwig()->render('widgets/documentSmallThumbnail.html.twig', array('document'=>$document)),
                        )
                    )),
                    Response::HTTP_OK,
                    array('content-type' => 'application/javascript')
                );

            } else {
                $msg = $this->getTranslator()->trans('document.cannot_persist');
                $request->getSession()->getFlashBag()->add('error', $msg);
                $this->getService('logger')->error($msg);

                return new Response(
                    json_encode(array(
                        "error" => $msg
                    )),
                    Response::HTTP_NOT_FOUND,
                    array('content-type' => 'application/javascript')
                );
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
            ->find('RZ\Roadiz\Core\Entities\Document', (int) $documentId);

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
     * @param RZ\Roadiz\Core\Entities\Document $doc
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
     * @param array $documentsIds
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildBulkDeleteForm($documentsIds)
    {
        $defaults = array();
        $builder = $this->getService('formFactory')
                    ->createBuilder('form', $defaults)
                    ->add('checksum', 'hidden', array(
                        'data' => md5(serialize($documentsIds)),
                        'constraints' => array(
                            new NotBlank()
                        )
                    ));

        return $builder->getForm();
    }

    /**
     * @param array $documentsIds
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildBulkDownloadForm($documentsIds)
    {
        $defaults = array();
        $builder = $this->getService('formFactory')
                    ->createBuilder('form', $defaults)
                    ->add('checksum', 'hidden', array(
                        'data' => md5(serialize($documentsIds)),
                        'constraints' => array(
                            new NotBlank()
                        )
                    ));

        return $builder->getForm();
    }

    /**
     * @param RZ\Roadiz\Core\Entities\Document $document
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildEditForm(Document $document)
    {
        $defaults = array(
            'private' => $document->isPrivate(),
            'filename' => $document->getFilename()
        );

        $builder = $this->getService('formFactory')
                    ->createBuilder('form', $defaults)
                    ->add('filename', 'text', array(
                        'label' => $this->getTranslator()->trans('filename'),
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
    private function buildUploadForm($folderId = null)
    {
        $builder = $this->getService('formFactory')
                    ->createBuilder('form', array(), array(
                        'csrf_protection' => false,
                        'csrf_field_name' => '_token',
                        // a unique key to help generate the secret token
                        'intention'       => static::AJAX_TOKEN_INTENTION,
                    ))
                    ->add('attachment', 'file', array(
                        'label' => $this->getTranslator()->trans('choose.documents.to_upload')
                    ));

        if (null !== $folderId &&
            $folderId > 0) {
            $builder->add('folderId', 'hidden', array(
                'data' => $folderId
            ));
        }

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

    /**
     * @param RZ\Roadiz\Core\Entities\Node $node
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildLinkFoldersForm()
    {
        $builder = $this->getService('formFactory')
                    ->createNamedBuilder('folderForm')
                    ->add('documentsId', 'hidden', array(
                        'attr' => array('class' => 'document-id-bulk-folder'),
                        'constraints' => array(
                            new NotBlank()
                        )
                    ))
                    ->add('folderPaths', 'text', array(
                        'label' => false,
                        'attr' => array(
                            'class' => 'rz-folder-autocomplete',
                            'placeholder' => $this->getTranslator()->trans('list.folders.to_link')
                        ),
                        'constraints' => array(
                            new NotBlank()
                        )
                    ))
                    ->add('submitFolder', 'submit', array(
                        'label' => $this->getTranslator()->trans('link.folders'),
                        'attr' => array(
                            'class' => 'uk-button uk-button-primary',
                            'title' => $this->getTranslator()->trans('link.folders'),
                            'data-uk-tooltip' => "{animation:true}"
                        )
                    ))
                    ->add('submitUnfolder', 'submit', array(
                        'label' => $this->getTranslator()->trans('unlink.folders'),
                        'attr' => array(
                            'class' => 'uk-button',
                            'title' => $this->getTranslator()->trans('unlink.folders'),
                            'data-uk-tooltip' => "{animation:true}"
                        )
                    ));

        return $builder->getForm();
    }

    /**
     * @param array $data
     *
     * @return string
     */
    private function joinFolder($data)
    {
        $msg = $this->getTranslator()->trans('no_documents.linked_to.folders');

        if (!empty($data['documentsId']) &&
            !empty($data['folderPaths'])) {

            $documentsIds = explode(',', $data['documentsId']);

            $documents = $this->getService('em')
                    ->getRepository('RZ\Roadiz\Core\Entities\Document')
                    ->findBy(array(
                        'id' => $documentsIds
                    ));

            $folderPaths = explode(',', $data['folderPaths']);
            $folderPaths = array_filter($folderPaths);

            foreach ($folderPaths as $path) {

                $folder = $this->getService('em')
                            ->getRepository('RZ\Roadiz\Core\Entities\Folder')
                            ->findOrCreateByPath($path);

                /*
                 * Add each selected documents
                 */
                foreach ($documents as $document) {
                    $folder->addDocument($document);
                }

                $this->getService('em')->flush();
            }

            $msg = $this->getTranslator()->trans('documents.linked_to.folders');
        }

        return $msg;
    }

    /**
     * @param array $data
     *
     * @return string
     */
    private function leaveFolder($data)
    {
        $msg = $this->getTranslator()->trans('no_documents.removed_from.folders');

        if (!empty($data['documentsId']) &&
            !empty($data['folderPaths'])) {

            $documentsIds = explode(',', $data['documentsId']);

            $documents = $this->getService('em')
                    ->getRepository('RZ\Roadiz\Core\Entities\Document')
                    ->findBy(array(
                        'id' => $documentsIds
                    ));

            $folderPaths = explode(',', $data['folderPaths']);
            $folderPaths = array_filter($folderPaths);

            foreach ($folderPaths as $path) {

                $folder = $this->getService('em')
                            ->getRepository('RZ\Roadiz\Core\Entities\Folder')
                            ->findByPath($path);

                if (null !== $folder) {
                    /*
                     * Add each selected documents
                     */
                    foreach ($documents as $document) {
                        $folder->removeDocument($document);
                    }

                    $this->getService('em')->flush();
                }
            }

            $msg = $this->getTranslator()->trans('documents.removed_from.folders');
        }

        return $msg;
    }
    /**
     * @param array $documents
     *
     * @return @return Symfony\Component\HttpFoundation\Response
     */
    private function downloadDocuments($documents)
    {
        if (count($documents) > 0) {

            $tmpFileName = tempnam("/tmp", "rzdocs_");
            $zip = new \ZipArchive();
            $zip->open($tmpFileName, \ZipArchive::CREATE);

            foreach ($documents as $document) {

                $zip->addFile($document->getAbsolutePath(), $document->getFilename());
            }

            $zip->close();

            $response = new Response(
                file_get_contents($tmpFileName),
                Response::HTTP_OK,
                array(
                    'content-control' => 'private',
                    'content-type' => 'application/zip',
                    'content-length' => filesize($tmpFileName),
                    'content-disposition' => 'attachment; filename=documents_archive.zip'
                )
            );

            unlink($tmpFileName);

            return $response;

        } else {
            return $this->throw404();
        }
    }

    private function embedDocument($data, $folderId = null)
    {
        $handlers = $this->getService('document.platforms');

        if (isset($data['embedId']) &&
            isset($data['embedPlatform']) &&
            in_array($data['embedPlatform'], array_keys($handlers))) {

            $class = $handlers[$data['embedPlatform']];
            $finder = new $class($data['embedId']);

            if ($finder->exists()) {

                $document = $finder->createDocumentFromFeed($this->getService());

                if (null !== $document &&
                    null !== $folderId &&
                    $folderId > 0) {

                    $folder = $this->getService('em')
                                   ->find('RZ\Roadiz\Core\Entities\Folder', (int) $folderId);

                    $document->addFolder($folder);
                    $folder->addDocument($document);
                    $this->getService('em')->flush();
                }

                return $document;

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
     * @return RZ\Roadiz\Core\Entities\Document
     */
    public function randomDocument($folderId = null)
    {
        $finder = new SplashbasePictureFinder();
        $document = $finder->createDocumentFromFeed($this->getService());

        if (null !== $document &&
            null !== $folderId &&
            $folderId > 0) {

            $folder = $this->getService('em')
                           ->find('RZ\Roadiz\Core\Entities\Folder', (int) $folderId);

            $document->addFolder($folder);
            $folder->addDocument($document);
            $this->getService('em')->flush();
        }
        return $document;
    }

    /**
     * @param array                           $data
     * @param RZ\Roadiz\Core\Entities\Document $document
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
    private function uploadDocument($data, $folderId = null)
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

                    if (null !== $folderId && $folderId > 0) {

                        $folder = $this->getService('em')
                                       ->find('RZ\Roadiz\Core\Entities\Folder', (int) $folderId);

                        $document->addFolder($folder);
                        $folder->addDocument($document);
                        $this->getService('em')->flush();
                    }

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
