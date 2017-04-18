<?php
/**
 * Copyright (c) 2016. Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
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
 * @file DocumentsController.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */
namespace Themes\Rozier\Controllers\Documents;

use Intervention\Image\Exception\InvalidArgumentException;
use RZ\Roadiz\CMS\Forms\Constraints\UniqueFilename;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Entities\Folder;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Events\DocumentEvents;
use RZ\Roadiz\Core\Events\FilterDocumentEvent;
use RZ\Roadiz\Utils\Asset\Packages;
use RZ\Roadiz\Utils\Document\DocumentFactory;
use RZ\Roadiz\Utils\MediaFinders\AbstractEmbedFinder;
use RZ\Roadiz\Utils\MediaFinders\SoundcloudEmbedFinder;
use RZ\Roadiz\Utils\MediaFinders\SplashbasePictureFinder;
use RZ\Roadiz\Utils\MediaFinders\YoutubeEmbedFinder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Themes\Rozier\AjaxControllers\AjaxDocumentsExplorerController;
use Themes\Rozier\Models\DocumentModel;
use Themes\Rozier\RozierApp;
use Themes\Rozier\Utils\SessionListFilters;

/**
 * Class DocumentsController
 * @package Themes\Rozier\Controllers\Documents
 */
class DocumentsController extends RozierApp
{
    protected $thumbnailFormat = [
        'quality' => 50,
        'fit' => '128x128',
        'inline' => false,
    ];

    /**
     * @param Request $request
     * @param null $folderId
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     * @throws \Twig_Error_Runtime
     */
    public function indexAction(Request $request, $folderId = null)
    {
        $this->validateAccessForRole('ROLE_ACCESS_DOCUMENTS');

        /** @var Translation $translation */
        $translation = $this->get('em')
            ->getRepository('RZ\Roadiz\Core\Entities\Translation')
            ->findDefault();

        $prefilters = [];

        if (null !== $folderId &&
            $folderId > 0) {
            $folder = $this->get('em')
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
        $listManager->setItemPerPage(static::DEFAULT_ITEM_PER_PAGE);

        /*
         * Stored in session
         */
        $sessionListFilter = new SessionListFilters('documents_item_per_page');
        $sessionListFilter->handleItemPerPage($request, $listManager);

        $listManager->handle();

        $this->assignation['filters'] = $listManager->getAssignation();
        $this->assignation['documents'] = $listManager->getEntities();
        $this->assignation['translation'] = $translation;
        $this->assignation['thumbnailFormat'] = $this->thumbnailFormat;

        return $this->render('documents/list.html.twig', $this->assignation);
    }

    /**
     * @param Request $request
     * @param int     $documentId
     *
     * @return Response
     */
    public function editAction(Request $request, $documentId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_DOCUMENTS');

        $document = $this->get('em')
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

                $this->get("dispatcher")->dispatch(
                    DocumentEvents::DOCUMENT_UPDATED,
                    new FilterDocumentEvent($document)
                );

                $routeParams = ['documentId' => $document->getId()];

                if ($form->get('referer')->getData()) {
                    $routeParams = array_merge($routeParams, [
                        'referer' => $form->get('referer')->getData()
                    ]);
                }

                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                return $this->redirect($this->generateUrl(
                    'documentsEditPage',
                    $routeParams
                ));
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('documents/edit.html.twig', $this->assignation);
        }

        throw new ResourceNotFoundException();
    }

    /**
     * @param Request $request
     * @param int     $documentId
     *
     * @return Response
     */
    public function previewAction(Request $request, $documentId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_DOCUMENTS');

        /** @var Document $document */
        $document = $this->get('em')
            ->find('RZ\Roadiz\Core\Entities\Document', (int) $documentId);

        if ($document !== null) {
            /** @var Packages $packages */
            $packages = $this->get('assetPackages');
            $documentPath = $packages->getDocumentFilePath($document);

            $this->assignation['document'] = $document;
            $this->assignation['thumbnailFormat'] = [
                'width' => 750,
                'controls' => true,
            ];
            if (file_exists($documentPath)) {
                $this->assignation['infos'] = [
                    'filesize' => sprintf('%.3f MB', (filesize($documentPath))/pow(1024, 2)),
                ];
                if ($document->isImage()) {
                    list($width, $height) = getimagesize($documentPath);
                    $this->assignation['infos']['width'] = $width . 'px';
                    $this->assignation['infos']['height'] = $height . 'px';
                }
            }

            return $this->render('documents/preview.html.twig', $this->assignation);
        }

        throw new ResourceNotFoundException();
    }

    /**
     * Return an deletion form for requested document.
     *
     * @param Request $request
     * @param int     $documentId
     *
     * @return Response
     */
    public function deleteAction(Request $request, $documentId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_DOCUMENTS_DELETE');

        $document = $this->get('em')
            ->find('RZ\Roadiz\Core\Entities\Document', (int) $documentId);

        if ($document !== null) {
            $this->assignation['document'] = $document;
            $form = $this->buildDeleteForm($document);
            $form->handleRequest($request);

            if ($form->isValid() &&
                $form->getData()['documentId'] == $document->getId()) {
                try {
                    $this->get("dispatcher")->dispatch(
                        DocumentEvents::DOCUMENT_DELETED,
                        new FilterDocumentEvent($document)
                    );
                    $this->get('em')->remove($document);
                    $this->get('em')->flush();
                    $msg = $this->getTranslator()->trans('document.%name%.deleted', ['%name%' => $document->getFilename()]);
                    $this->publishConfirmMessage($request, $msg);
                } catch (\Exception $e) {
                    $msg = $this->getTranslator()->trans('document.%name%.cannot_delete', ['%name%' => $document->getFilename()]);
                    $this->get('logger')->error($e->getMessage());
                    $this->publishErrorMessage($request, $msg);
                }
                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                return $this->redirect($this->generateUrl('documentsHomePage'));
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('documents/delete.html.twig', $this->assignation);
        }

        throw new ResourceNotFoundException();
    }

    /**
     * Return an deletion form for multiple docs.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function bulkDeleteAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_DOCUMENTS_DELETE');

        $documentsIds = $request->get('documents');

        $documents = $this->get('em')
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
                    $this->get('em')->remove($document);
                    $msg = $this->getTranslator()->trans(
                        'document.%name%.deleted',
                        ['%name%' => $document->getFilename()]
                    );
                    $this->publishConfirmMessage($request, $msg);
                }
                $this->get('em')->flush();

                return $this->redirect($this->generateUrl('documentsHomePage'));
            }

            $this->assignation['form'] = $form->createView();
            $this->assignation['action'] = '?' . http_build_query(['documents' => $documentsIds]);
            $this->assignation['thumbnailFormat'] = $this->thumbnailFormat;

            return $this->render('documents/bulkDelete.html.twig', $this->assignation);
        }

        throw new ResourceNotFoundException();
    }

    /**
     * Return an deletion form for multiple docs.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function bulkDownloadAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_DOCUMENTS');

        $documentsIds = $request->get('documents');

        $documents = $this->get('em')
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
        }

        throw new ResourceNotFoundException();
    }

    /**
     * Embed external document page.
     *
     * @param Request $request
     * @param int     $folderId
     *
     * @return Response
     * @throws \Twig_Error_Runtime
     */
    public function embedAction(Request $request, $folderId = null)
    {
        $this->validateAccessForRole('ROLE_ACCESS_DOCUMENTS');

        if (null !== $folderId &&
            $folderId > 0) {
            $folder = $this->get('em')
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

                $this->get("dispatcher")->dispatch(
                    DocumentEvents::DOCUMENT_CREATED,
                    new FilterDocumentEvent($document)
                );
                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                return $this->redirect($this->generateUrl('documentsHomePage', ['folderId' => $folderId]));
            } catch (\RuntimeException $e) {
                $form->addError(new FormError($this->getTranslator()->trans($e->getMessage())));
            } catch (\InvalidArgumentException $e) {
                $form->addError(new FormError($this->getTranslator()->trans($e->getMessage())));
            }
        }

        $this->assignation['form'] = $form->createView();

        return $this->render('documents/embed.html.twig', $this->assignation);
    }

    /**
     * Get random external document page.
     *
     * @param Request $request
     * @param int     $folderId
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

            $this->get("dispatcher")->dispatch(
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
     * @param int     $documentId
     *
     * @return Response
     */
    public function downloadAction(Request $request, $documentId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_DOCUMENTS');

        /** @var Document $document */
        $document = $this->get('em')
            ->find('RZ\Roadiz\Core\Entities\Document', (int) $documentId);

        if ($document !== null &&
            null !== $response = $document->getHandler()->getDownloadResponse()) {
            return $response->send();
        }

        throw new ResourceNotFoundException();
    }

    /**
     * @param Request $request
     * @param int $folderId
     * @param string $_format
     * @return Response
     */
    public function uploadAction(Request $request, $folderId = null, $_format = 'html')
    {
        $this->validateAccessForRole('ROLE_ACCESS_DOCUMENTS');

        if (null !== $folderId &&
            $folderId > 0) {
            $folder = $this->get('em')
                ->find('RZ\Roadiz\Core\Entities\Folder', (int) $folderId);

            $this->assignation['folder'] = $folder;
        }

        /*
         * Handle main form
         */
        $form = $this->buildUploadForm($folderId);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $document = $this->uploadDocument($form, $folderId);

                if (false !== $document) {
                    $msg = $this->getTranslator()->trans('document.%name%.uploaded', [
                        '%name%' => $document->getFilename(),
                    ]);
                    $this->publishConfirmMessage($request, $msg);

                    $this->get("dispatcher")->dispatch(
                        DocumentEvents::DOCUMENT_CREATED,
                        new FilterDocumentEvent($document)
                    );

                    if ($_format === 'json' || $request->isXmlHttpRequest()) {
                        $documentModel = new DocumentModel($document, $this->getContainer());
                        return new JsonResponse([
                            'success' => true,
                            'documentId' => $document->getId(),
                            'document' => $documentModel->toArray(),
                            'thumbnail' => [
                                'id' => $document->getId(),
                                'filename' => $document->getFilename(),
                                'large' => $document->getViewer()->getDocumentUrlByArray(['noProcess' => true]),
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

                    if ($_format === 'json' || $request->isXmlHttpRequest()) {
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
            } elseif ($_format === 'json' || $request->isXmlHttpRequest()) {
                /*
                 * Bad form submitted
                 */
                $errorPerForm = [];
                /** @var Form $child */
                foreach ($form as $child) {
                    if (!$child->isValid()) {
                        foreach ($child->getErrors() as $error) {
                            $errorPerForm[$child->getName()][] = $this->get('translator')->trans($error->getMessage());
                        }
                    }
                }
                return new JsonResponse(
                    [
                        "errors" => $errorPerForm,
                    ],
                    Response::HTTP_BAD_REQUEST
                );
            }
        }
        $this->assignation['form'] = $form->createView();
        $this->assignation['maxUploadSize'] = \Symfony\Component\HttpFoundation\File\UploadedFile::getMaxFilesize() / 1024 / 1024;

        return $this->render('documents/upload.html.twig', $this->assignation);
    }

    /**
     * Return a node list using this document.
     *
     * @param Request $request
     * @param int     $documentId
     *
     * @return Response
     */
    public function usageAction(Request $request, $documentId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_DOCUMENTS');

        $document = $this->get('em')
            ->find('RZ\Roadiz\Core\Entities\Document', (int) $documentId);

        if ($document !== null) {
            $this->assignation['document'] = $document;
            $this->assignation['usages'] = $document->getNodesSourcesByFields();

            return $this->render('documents/usage.html.twig', $this->assignation);
        }

        throw new ResourceNotFoundException();
    }

    /**
     * @param Document $doc
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
     * @param Document $document
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
            ->add('referer', 'hidden', [
                'data' => $this->get('request')->get('referer'),
                'mapped' => false,
            ])
            ->add('filename', 'text', [
                'label' => 'filename',
                'required' => false,
                'constraints' => [
                    // must ends with file extension
                    new Regex([
                        'pattern' => '/\.[a-z0-9]+$/i',
                        'htmlPattern' => ".[a-z0-9]+$",
                        'message' => 'value_is_not_a_valid_filename'
                    ]),
                    new UniqueFilename([
                        'document' => $document,
                        'packages' => $this->get('assetPackages'),
                    ]),
                ],
            ])
            ->add('private', 'checkbox', [
                'label' => 'private',
                'required' => false,
            ])
            ->add('newDocument', 'file', [
                'label' => 'overwrite.document',
                'required' => false,
                'constraints' => [
                    new File()
                ],
            ]);

        return $builder->getForm();
    }

    /**
     * @param int $folderId
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildUploadForm($folderId = null)
    {
        $builder = $this->get('formFactory')
            ->createBuilder('form', [], [
                'csrf_protection' => false,
                'csrf_field_name' => '_token',
                // a unique key to help generate the secret token
                'intention' => static::AJAX_TOKEN_INTENTION,
            ])
            ->add('attachment', 'file', [
                'label' => 'choose.documents.to_upload',
                'constraints' => [
                    new File(),
                ],
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
     * @return \Symfony\Component\Form\Form
     */
    private function buildEmbedForm()
    {
        $services = [];
        foreach (array_keys($this->get('document.platforms')) as $value) {
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
     * @return \Symfony\Component\Form\Form
     */
    private function buildLinkFoldersForm()
    {
        $builder = $this->get('formFactory')
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
                'label' => false,
                'attr' => [
                    'class' => 'uk-button uk-button-primary',
                    'title' => 'link.folders',
                    'data-uk-tooltip' => "{animation:true}",
                ],
            ])
            ->add('submitUnfolder', 'submit', [
                'label' => false,
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

            $documents = $this->get('em')
                ->getRepository('RZ\Roadiz\Core\Entities\Document')
                ->findBy([
                    'id' => $documentsIds,
                ]);

            $folderPaths = explode(',', $data['folderPaths']);
            $folderPaths = array_filter($folderPaths);

            foreach ($folderPaths as $path) {
                /** @var Folder $folder */
                $folder = $this->get('em')
                    ->getRepository('RZ\Roadiz\Core\Entities\Folder')
                    ->findOrCreateByPath($path);

                /*
                 * Add each selected documents
                 */
                foreach ($documents as $document) {
                    $folder->addDocument($document);
                }
            }

            $this->get('em')->flush();
            $msg = $this->getTranslator()->trans('documents.linked_to.folders');

            /*
             * Dispatch events
             */
            foreach ($documents as $document) {
                $this->get("dispatcher")->dispatch(
                    DocumentEvents::DOCUMENT_IN_FOLDER,
                    new FilterDocumentEvent($document)
                );
            }
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

            $documents = $this->get('em')
                ->getRepository('RZ\Roadiz\Core\Entities\Document')
                ->findBy([
                    'id' => $documentsIds,
                ]);

            $folderPaths = explode(',', $data['folderPaths']);
            $folderPaths = array_filter($folderPaths);

            foreach ($folderPaths as $path) {
                /** @var Folder $folder */
                $folder = $this->get('em')
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
            $this->get('em')->flush();
            $msg = $this->getTranslator()->trans('documents.removed_from.folders');

            /*
             * Dispatch events
             */
            foreach ($documents as $document) {
                $this->get("dispatcher")->dispatch(
                    DocumentEvents::DOCUMENT_OUT_FOLDER,
                    new FilterDocumentEvent($document)
                );
            }
        }

        return $msg;
    }
    /**
     * @param array $documents
     *
     * @return Response
     */
    private function downloadDocuments($documents)
    {
        if (count($documents) > 0) {
            $tmpFileName = tempnam("/tmp", "rzdocs_");
            $zip = new \ZipArchive();
            $zip->open($tmpFileName, \ZipArchive::CREATE);

            /** @var Document $document */
            foreach ($documents as $document) {
                $documentPath = $this->get('assetPackages')->getDocumentFilePath($document);
                $zip->addFile($documentPath, $document->getFilename());
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
        }

        throw new ResourceNotFoundException();
    }

    /**
     * @param array $data
     * @param int   $folderId
     *
     * @return Document
     * @throws \Exception
     * @throws \RZ\Roadiz\Core\Exceptions\EntityAlreadyExistsException
     */
    private function embedDocument($data, $folderId = null)
    {
        $handlers = $this->get('document.platforms');

        if (isset($data['embedId']) &&
            isset($data['embedPlatform']) &&
            in_array($data['embedPlatform'], array_keys($handlers))) {
            $class = $handlers[$data['embedPlatform']];

            /** @var AbstractEmbedFinder $finder */
            $finder = new $class($data['embedId']);

            if ($finder instanceof YoutubeEmbedFinder) {
                $finder->setKey($this->get('settingsBag')->get('google_server_id'));
            }
            if ($finder instanceof SoundcloudEmbedFinder) {
                $finder->setKey($this->get('settingsBag')->get('soundcloud_client_id'));
            }

            if ($finder->exists()) {
                $document = $finder->createDocumentFromFeed($this->getContainer());

                if (null !== $document &&
                    null !== $folderId &&
                    $folderId > 0) {
                    /** @var Folder $folder */
                    $folder = $this->get('em')
                        ->find('RZ\Roadiz\Core\Entities\Folder', (int) $folderId);

                    $document->addFolder($folder);
                    $folder->addDocument($document);
                    $this->get('em')->flush();
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
     * @param int $folderId
     *
     * @return Document
     * @throws \Exception
     * @throws \RZ\Roadiz\Core\Exceptions\EntityAlreadyExistsException
     */
    public function randomDocument($folderId = null)
    {
        $finder = new SplashbasePictureFinder();
        $document = $finder->createDocumentFromFeed($this->getContainer());

        if (null !== $document &&
            null !== $folderId &&
            $folderId > 0) {
            $folder = $this->get('em')
                ->find('RZ\Roadiz\Core\Entities\Folder', (int) $folderId);

            $document->addFolder($folder);
            $folder->addDocument($document);
            $this->get('em')->flush();
        }
        return $document;
    }

    /**
     * @param array    $data
     * @param Document $document
     */
    private function editDocument($data, Document $document)
    {
        /*
         * Rename document file
         */
        if (!empty($data['filename']) &&
            $data['filename'] != $document->getFilename()) {

            /** @var Packages $packages */
            $packages = $this->get('assetPackages');
            $oldPath = $packages->getDocumentFilePath($document);

            $fs = new Filesystem();
            /*
             * If file exists, just rename it
             */
            // set filename to clean given string before renaming file.
            $document->setFilename($data['filename']);
            $newPath = $packages->getDocumentFilePath($document);
            $fs->rename(
                $oldPath,
                $newPath
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

        $this->get('em')->flush();
    }

    /**
     * @param $data
     * @param Document $document
     * @return Document
     */
    private function updateDocument($data, Document $document)
    {
        if (!empty($data['newDocument'])) {
            /** @var UploadedFile $uploadedFile */
            $uploadedFile = $data['newDocument'];

            $documentFactory = new DocumentFactory(
                $uploadedFile,
                $this->get('em'),
                $this->get('dispatcher'),
                $this->get('assetPackages'),
                null,
                $this->get('logger')
            );

            $documentFactory->updateDocument($document);
            $this->get('em')->flush();
        }
        return $document;
    }

    /**
     * Handle upload form data to create a Document.
     *
     * @param \Symfony\Component\Form\Form $data
     * @param int                          $folderId
     *
     * @return bool|Document
     */
    private function uploadDocument($data, $folderId = null)
    {
        $folder = null;
        if (null !== $folderId && $folderId > 0) {
            /** @var Folder $folder */
            $folder = $this->get('em')
                ->find('RZ\Roadiz\Core\Entities\Folder', (int) $folderId);
        }

        if (!empty($data['attachment'])) {
            $uploadedFile = $data['attachment']->getData();

            $documentFactory = new DocumentFactory(
                $uploadedFile,
                $this->get('em'),
                $this->get('dispatcher'),
                $this->get('assetPackages'),
                $folder,
                $this->get('logger')
            );

            if (null !== $document = $documentFactory->getDocument()) {
                $this->get('em')->flush();
                return $document;
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
        $this->assignation['documents'] = $this->get('em')
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
