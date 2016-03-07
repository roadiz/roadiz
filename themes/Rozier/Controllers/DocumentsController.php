<?php
/*
 * Copyright © 2014, Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 *
 * @file DocumentsController.php
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Controllers;

use RZ\Roadiz\Core\Bags\SettingsBag;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Events\DocumentEvents;
use RZ\Roadiz\Core\Events\FilterDocumentEvent;
use RZ\Roadiz\Utils\MediaFinders\SoundcloudEmbedFinder;
use RZ\Roadiz\Utils\MediaFinders\SplashbasePictureFinder;
use RZ\Roadiz\Utils\MediaFinders\YoutubeEmbedFinder;
use RZ\Roadiz\Utils\StringHandler;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\NotBlank;
use Themes\Rozier\AjaxControllers\AjaxDocumentsExplorerController;
use Themes\Rozier\RozierApp;

/**
 * {@inheritdoc}
 */
class DocumentsController extends RozierApp
{
    protected $thumbnailFormat = [
        'quality' => 50,
        'fit' => '128x128',
    ];

    /**
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request, $folderId = null)
    {
        $this->validateAccessForRole('ROLE_ACCESS_DOCUMENTS');

        $prefilters = [];

        if (null !== $folderId &&
            $folderId > 0) {
            $folder = $this->getService('em')
                ->find('RZ\Roadiz\Core\Entities\Folder', (int) $folderId);

            $prefilters['folders'] = [$folder];
            $this->assignation['folder'] = $folder;
        }

        /*
         * Handle bulk folder form
         */
        $joinFolderForm = $this->buildLinkFoldersForm();
        $joinFolderForm->handleRequest($request);
        if ($joinFolderForm->isValid()) {
            $data = $joinFolderForm->getData();

            if ($joinFolderForm->get('submitFolder')->isClicked()) {
                $msg = $this->joinFolder($data);
            } elseif ($joinFolderForm->get('submitUnfolder')->isClicked()) {
                $msg = $this->leaveFolder($data);
            } else {
                $msg = $this->getTranslator()->trans('wrong.request');
            }

            $this->publishConfirmMessage($request, $msg);

            return $this->redirect($this->generateUrl(
                'documentsHomePage',
                ['folderId' => $folderId]
            ));
        }
        $this->assignation['joinFolderForm'] = $joinFolderForm->createView();

        /*
         * Manage get request to filter list
         */
        $listManager = $this->createEntityListManager(
            'RZ\Roadiz\Core\Entities\Document',
            $prefilters,
            ['createdAt' => 'DESC']
        );
        $listManager->setItemPerPage(28);
        $listManager->handle();

        $this->assignation['filters'] = $listManager->getAssignation();
        $this->assignation['documents'] = $listManager->getEntities();
        $this->assignation['thumbnailFormat'] = $this->thumbnailFormat;

        return $this->render('documents/list.html.twig', $this->assignation);
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
            $this->assignation['rawDocument'] = $document->getRawDocument();

            /*
             * Handle main form
             */
            $form = $this->buildEditForm($document);
            $form->handleRequest($request);

            if ($form->isValid()) {
                $data = $form->getData();

                /*
                 * Update document file
                 * if present
                 */
                if ($document !== null && !empty($data['newDocument'])) {
                    $document = $this->updateDocument($data, $document);
                    $data["filename"] = $document->getFilename();

                    $msg = $this->getTranslator()->trans('document.file.%name%.updated', [
                        '%name%' => $document->getFilename(),
                    ]);
                    $this->publishConfirmMessage($request, $msg);
                }
                unset($data['newDocument']);

                /*
                 * Update document common data
                 */
                $this->editDocument($data, $document);
                $msg = $this->getTranslator()->trans('document.%name%.updated', [
                    '%name%' => $document->getFilename(),
                ]);
                $this->publishConfirmMessage($request, $msg);

                $this->getService("dispatcher")->dispatch(
                    DocumentEvents::DOCUMENT_UPDATED,
                    new FilterDocumentEvent($document)
                );
                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                return $this->redirect($this->generateUrl(
                    'documentsEditPage',
                    ['documentId' => $document->getId()]
                ));
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('documents/edit.html.twig', $this->assignation);
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
            $this->assignation['thumbnailFormat'] = [
                'width' => 500,
                'controls' => true,
            ];

            return $this->render('documents/preview.html.twig', $this->assignation);
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
            $form->handleRequest($request);

            if ($form->isValid() &&
                $form->getData()['documentId'] == $document->getId()) {
                try {
                    $this->getService("dispatcher")->dispatch(
                        DocumentEvents::DOCUMENT_DELETED,
                        new FilterDocumentEvent($document)
                    );
                    $this->getService('em')->remove($document);
                    $this->getService('em')->flush();
                    $msg = $this->getTranslator()->trans('document.%name%.deleted', ['%name%' => $document->getFilename()]);
                    $this->publishConfirmMessage($request, $msg);
                } catch (\Exception $e) {
                    $msg = $this->getTranslator()->trans('document.%name%.cannot_delete', ['%name%' => $document->getFilename()]);
                    $this->getService('logger')->error($e->getMessage());
                    $this->publishErrorMessage($request, $msg);
                }
                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                return $this->redirect($this->generateUrl('documentsHomePage'));
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('documents/delete.html.twig', $this->assignation);
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
            ->findBy([
                'id' => $documentsIds,
            ]);

        if ($documents !== null &&
            count($documents) > 0) {
            $this->assignation['documents'] = $documents;
            $form = $this->buildBulkDeleteForm($documentsIds);

            $form->handleRequest($request);

            if ($form->isValid()) {
                foreach ($documents as $document) {
                    $this->getService('em')->remove($document);
                    $msg = $this->getTranslator()->trans(
                        'document.%name%.deleted',
                        ['%name%' => $document->getFilename()]
                    );
                    $this->publishConfirmMessage($request, $msg);
                }
                $this->getService('em')->flush();

                return $this->redirect($this->generateUrl('documentsHomePage'));
            }

            $this->assignation['form'] = $form->createView();
            $this->assignation['action'] = '?' . http_build_query(['documents' => $documentsIds]);
            $this->assignation['thumbnailFormat'] = $this->thumbnailFormat;

            return $this->render('documents/bulkDelete.html.twig', $this->assignation);
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
            ->findBy([
                'id' => $documentsIds,
            ]);

        if ($documents !== null &&
            count($documents) > 0) {
            $this->assignation['documents'] = $documents;
            $form = $this->buildBulkDownloadForm($documentsIds);

            $form->handleRequest($request);

            if ($form->isValid()) {
                try {
                    return $this->downloadDocuments($documents);

                } catch (\Exception $e) {
                    $msg = $this->getTranslator()->trans('documents.cannot_download');
                    $this->publishErrorMessage($request, $msg);
                }

                return $this->redirect($this->generateUrl('documentsHomePage'));
            }

            $this->assignation['form'] = $form->createView();
            $this->assignation['action'] = '?' . http_build_query(['documents' => $documentsIds]);
            $this->assignation['thumbnailFormat'] = $this->thumbnailFormat;

            return $this->render('documents/bulkDownload.html.twig', $this->assignation);
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

            $this->assignation['folder'] = $folder;
        }

        /*
         * Handle main form
         */
        $form = $this->buildEmbedForm();
        $form->handleRequest($request);

        if ($form->isValid()) {
            try {
                $document = $this->embedDocument($form->getData(), $folderId);

                $msg = $this->getTranslator()->trans('document.%name%.uploaded', [
                    '%name%' => $document->getFilename(),
                ]);
                $this->publishConfirmMessage($request, $msg);

                $this->getService("dispatcher")->dispatch(
                    DocumentEvents::DOCUMENT_CREATED,
                    new FilterDocumentEvent($document)
                );

            } catch (\Exception $e) {
                $this->publishErrorMessage($request, $e->getMessage());
            }
            /*
             * Force redirect to avoid resending form when refreshing page
             */
            return $this->redirect($this->generateUrl('documentsHomePage', ['folderId' => $folderId]));
        }

        $this->assignation['form'] = $form->createView();

        return $this->render('documents/embed.html.twig', $this->assignation);
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

            $msg = $this->getTranslator()->trans('document.%name%.uploaded', [
                '%name%' => $document->getFilename(),
            ]);
            $this->publishConfirmMessage($request, $msg);

            $this->getService("dispatcher")->dispatch(
                DocumentEvents::DOCUMENT_CREATED,
                new FilterDocumentEvent($document)
            );

        } catch (\Exception $e) {
            $this->publishErrorMessage(
                $request,
                $this->getTranslator()->trans($e->getMessage())
            );
        }
        /*
         * Force redirect to avoid resending form when refreshing page
         */
        return $this->redirect($this->generateUrl('documentsHomePage', ['folderId' => $folderId]));
    }

    /**
     * Download document file.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function downloadAction(Request $request, $documentId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_DOCUMENTS');

        $document = $this->getService('em')
            ->find('RZ\Roadiz\Core\Entities\Document', (int) $documentId);

        if ($document !== null &&
            null !== $response = $document->getHandler()->getDownloadResponse()) {
            return $response->send();
        } else {
            return $this->throw404();
        }
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

            $this->assignation['folder'] = $folder;
        }

        /*
         * Handle main form
         */
        $form = $this->buildUploadForm($folderId);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $document = $this->uploadDocument($form, $folderId);

            if (false !== $document) {
                $msg = $this->getTranslator()->trans('document.%name%.uploaded', [
                    '%name%' => $document->getFilename(),
                ]);
                $this->publishConfirmMessage($request, $msg);

                $this->getService("dispatcher")->dispatch(
                    DocumentEvents::DOCUMENT_CREATED,
                    new FilterDocumentEvent($document)
                );

                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse([
                        'success' => true,
                        'documentId' => $document->getId(),
                        'thumbnail' => [
                            'id' => $document->getId(),
                            'filename' => $document->getFilename(),
                            'thumbnail' => $document->getViewer()->getDocumentUrlByArray(AjaxDocumentsExplorerController::$thumbnailArray),
                            'html' => $this->getTwig()->render('widgets/documentSmallThumbnail.html.twig', ['document' => $document]),
                        ],
                    ]);
                } else {
                    return $this->redirect($this->generateUrl('documentsHomePage', ['folderId' => $folderId]));
                }

            } else {
                $msg = $this->getTranslator()->trans('document.cannot_persist');
                $this->publishErrorMessage($request, $msg);

                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse(
                        [
                            "error" => $msg,
                        ],
                        Response::HTTP_NOT_FOUND
                    );
                } else {
                    return $this->redirect($this->generateUrl('documentsHomePage', ['folderId' => $folderId]));
                }
            }
        }
        $this->assignation['form'] = $form->createView();
        $this->assignation['maxUploadSize'] = \Symfony\Component\HttpFoundation\File\UploadedFile::getMaxFilesize() / 1024 / 1024;

        return $this->render('documents/upload.html.twig', $this->assignation);
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

            return $this->render('documents/usage.html.twig', $this->assignation);
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
        $defaults = [
            'documentId' => $doc->getId(),
        ];
        $builder = $this->createFormBuilder($defaults)
            ->add('documentId', 'hidden', [
                'data' => $doc->getId(),
                'constraints' => [
                    new NotBlank(),
                ],
            ]);

        return $builder->getForm();
    }
    /**
     * @param array $documentsIds
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildBulkDeleteForm($documentsIds)
    {
        $defaults = [];
        $builder = $this->createFormBuilder($defaults)
            ->add('checksum', 'hidden', [
                'data' => md5(serialize($documentsIds)),
                'constraints' => [
                    new NotBlank(),
                ],
            ]);

        return $builder->getForm();
    }

    /**
     * @param array $documentsIds
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildBulkDownloadForm($documentsIds)
    {
        $defaults = [];
        $builder = $this->createFormBuilder($defaults)
            ->add('checksum', 'hidden', [
                'data' => md5(serialize($documentsIds)),
                'constraints' => [
                    new NotBlank(),
                ],
            ]);

        return $builder->getForm();
    }

    /**
     * @param RZ\Roadiz\Core\Entities\Document $document
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildEditForm(Document $document)
    {
        $defaults = [
            'private' => $document->isPrivate(),
            'filename' => $document->getFilename(),
            'newDocument' => null,
        ];

        $builder = $this->createFormBuilder($defaults)
            ->add('filename', 'text', [
                'label' => 'filename',
                'required' => false,
            ])
            ->add('private', 'checkbox', [
                'label' => 'private',
                'required' => false,
            ])
            ->add('newDocument', 'file', [
                'label' => 'overwrite.document',
                'required' => false,
            ]);

        return $builder->getForm();
    }

    /**
     * @return Symfony\Component\Form\Form
     */
    private function buildUploadForm($folderId = null)
    {
        $builder = $this->getService('formFactory')
            ->createBuilder('form', [], [
                'csrf_protection' => false,
                'csrf_field_name' => '_token',
                // a unique key to help generate the secret token
                'intention' => static::AJAX_TOKEN_INTENTION,
            ])
            ->add('attachment', 'file', [
                'label' => 'choose.documents.to_upload',
            ]);

        if (null !== $folderId &&
            $folderId > 0) {
            $builder->add('folderId', 'hidden', [
                'data' => $folderId,
            ]);
        }

        return $builder->getForm();
    }

    /**
     * @return Symfony\Component\Form\Form
     */
    private function buildEmbedForm()
    {
        $services = [];
        foreach (array_keys($this->getService('document.platforms')) as $value) {
            $services[$value] = ucwords($value);
        }

        $builder = $this->createFormBuilder()
            ->add('embedId', 'text', [
                'label' => 'document.embedId',
            ])
            ->add('embedPlatform', 'choice', [
                'label' => 'document.platform',
                'choices' => $services,
            ]);

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
            ->add('documentsId', 'hidden', [
                'attr' => ['class' => 'document-id-bulk-folder'],
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('folderPaths', 'text', [
                'label' => false,
                'attr' => [
                    'class' => 'rz-folder-autocomplete',
                    'placeholder' => 'list.folders.to_link',
                ],
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('submitFolder', 'submit', [
                'label' => 'link.folders',
                'attr' => [
                    'class' => 'uk-button uk-button-primary',
                    'title' => 'link.folders',
                    'data-uk-tooltip' => "{animation:true}",
                ],
            ])
            ->add('submitUnfolder', 'submit', [
                'label' => 'unlink.folders',
                'attr' => [
                    'class' => 'uk-button',
                    'title' => 'unlink.folders',
                    'data-uk-tooltip' => "{animation:true}",
                ],
            ]);

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
                ->findBy([
                    'id' => $documentsIds,
                ]);

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
            }

            $this->getService('em')->flush();
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
                ->findBy([
                    'id' => $documentsIds,
                ]);

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
                }
            }
            $this->getService('em')->flush();

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
                [
                    'content-control' => 'private',
                    'content-type' => 'application/zip',
                    'content-length' => filesize($tmpFileName),
                    'content-disposition' => 'attachment; filename=documents_archive.zip',
                ]
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

            if ($finder instanceof YoutubeEmbedFinder) {
                $finder->setKey(SettingsBag::get('google_server_id'));
            }
            if ($finder instanceof SoundcloudEmbedFinder) {
                $finder->setKey(SettingsBag::get('soundcloud_client_id'));
            }

            if ($finder->exists()) {
                $document = $finder->createDocumentFromFeed($this->getContainer());

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
        $document = $finder->createDocumentFromFeed($this->getContainer());

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
        /*
         * Rename document file
         */
        if (!empty($data['filename']) &&
            $data['filename'] != $document->getFilename()) {
            $oldUrl = $document->getAbsolutePath();
            $fs = new Filesystem();
            /*
             * If file exists, just rename it
             */
            // set filename to clean given string before renaming file.
            $document->setFilename($data['filename']);
            $fs->rename(
                $oldUrl,
                $document->getAbsolutePath()
            );

            unset($data['filename']);
        }

        /*
         * Change privacy document status
         */
        if ($data['private'] != $document->isPrivate()) {
            if ($data['private'] === true) {
                $document->getHandler()->makePrivate();
            } else {
                $document->getHandler()->makePublic();
            }

            unset($data['private']);
        }

        foreach ($data as $key => $value) {
            $setter = 'set' . ucwords($key);
            $document->$setter($value);
        }

        $this->getService('em')->flush();
    }

    private function updateDocument($data, $document)
    {
        $fs = new Filesystem();

        if (!empty($data['newDocument'])) {
            $uploadedFile = $data['newDocument'];

            if ($uploadedFile !== null &&
                $uploadedFile->getError() == UPLOAD_ERR_OK &&
                $uploadedFile->isValid()) {
                /*
                 * In case file already exists
                 */
                if ($fs->exists($document->getAbsolutePath())) {
                    $fs->remove($document->getAbsolutePath());
                }

                if (StringHandler::cleanForFilename($uploadedFile->getClientOriginalName()) == $document->getFilename()) {
                    $finder = new Finder();

                    $previousFolder = $document->getFilesFolder() . '/' . $document->getFolder();

                    if ($fs->exists($previousFolder)) {
                        $finder->files()->in($previousFolder);
                        // Remove Precious folder if it's empty
                        if ($finder->count() == 0) {
                            $fs->remove($previousFolder);
                        }
                    }

                    $document->setFolder(substr(hash("crc32b", date('YmdHi')), 0, 12));
                }

                $document->setFilename($uploadedFile->getClientOriginalName());
                $document->setMimeType($uploadedFile->getMimeType());
                $this->getService('em')->flush();

                $uploadedFile->move(Document::getFilesFolder() . '/' . $document->getFolder(), $document->getFilename());

                if ($document->isImage()) {
                    $this->getService("dispatcher")->dispatch(
                        DocumentEvents::DOCUMENT_IMAGE_UPLOADED,
                        new FilterDocumentEvent($document)
                    );
                }

                return $document;
            }
        }

        return $document;
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
            $uploadedFile = $data['attachment']->getData();

            if ($uploadedFile !== null &&
                $uploadedFile->getError() == UPLOAD_ERR_OK &&
                $uploadedFile->isValid()) {
                try {
                    $document = new Document();
                    $document->setFilename($uploadedFile->getClientOriginalName());
                    $document->setMimeType($uploadedFile->getMimeType());

                    /*
                     * Special case for SVG without XML statement
                     */
                    if (($document->getMimeType() == "text/plain" ||
                        $document->getMimeType() == 'text/html') &&
                        preg_match("#\.svg$#", $uploadedFile->getClientOriginalName())) {
                        $this->getService('logger')->debug('Uploaded a SVG without xml declaration. Presuming it’s a valid SVG file.');
                        $document->setMimeType('image/svg+xml');
                    }

                    $this->getService('em')->persist($document);
                    $this->getService('em')->flush();

                    if (null !== $folderId && $folderId > 0) {
                        $folder = $this->getService('em')
                            ->find('RZ\Roadiz\Core\Entities\Folder', (int) $folderId);

                        $document->addFolder($folder);
                        $folder->addDocument($document);
                        $this->getService('em')->flush();
                    }

                    $uploadedFile->move(
                        Document::getFilesFolder() . '/' . $document->getFolder(),
                        $document->getFilename()
                    );

                    if ($document->isImage()) {
                        $this->getService("dispatcher")->dispatch(
                            DocumentEvents::DOCUMENT_IMAGE_UPLOADED,
                            new FilterDocumentEvent($document)
                        );
                    }

                    return $document;
                } catch (\Exception $e) {
                    return false;
                }
            }
        }

        return false;
    }
    /**
     * See unused documents.
     *
     * @param  Request $request
     * @return Response
     */
    public function unusedAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_DOCUMENTS');

        $this->assignation['orphans'] = true;
        $this->assignation['documents'] = $this->getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\Document')
            ->findAllUnused();
        $this->assignation['filters'] = [
            'itemCount' => count($this->assignation['documents']),
            'itemPerPage' => false,
        ];
        $this->assignation['thumbnailFormat'] = $this->thumbnailFormat;

        return $this->render('documents/list.html.twig', $this->assignation);
    }
}
