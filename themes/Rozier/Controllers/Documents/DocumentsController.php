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

use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Entities\Folder;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Events\DocumentEvents;
use RZ\Roadiz\Core\Events\FilterDocumentEvent;
use RZ\Roadiz\Core\Exceptions\APINeedsAuthentificationException;
use RZ\Roadiz\Core\Handlers\DocumentHandler;
use RZ\Roadiz\Core\Models\DocumentInterface;
use RZ\Roadiz\Utils\Asset\Packages;
use RZ\Roadiz\Utils\Document\DocumentFactory;
use RZ\Roadiz\Utils\MediaFinders\AbstractEmbedFinder;
use RZ\Roadiz\Utils\MediaFinders\SoundcloudEmbedFinder;
use RZ\Roadiz\Utils\MediaFinders\SplashbasePictureFinder;
use RZ\Roadiz\Utils\MediaFinders\YoutubeEmbedFinder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Themes\Rozier\Forms\DocumentEditType;
use Themes\Rozier\Forms\DocumentEmbedType;
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
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function indexAction(Request $request, $folderId = null)
    {
        $this->validateAccessForRole('ROLE_ACCESS_DOCUMENTS');

        /** @var Translation $translation */
        $translation = $this->get('em')
            ->getRepository(Translation::class)
            ->findDefault();

        $prefilters = [
            'raw' => false,
        ];

        if (null !== $folderId &&
            $folderId > 0) {
            $folder = $this->get('em')
                ->find(Folder::class, (int) $folderId);

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
            Document::class,
            $prefilters,
            ['createdAt' => 'DESC']
        );
        $listManager->setDisplayingNotPublishedNodes(true);
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
     * @param $documentId
     * @return JsonResponse|Response
     */
    public function adjustAction(Request $request, $documentId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_DOCUMENTS');

        /** @var Document $document */
        $document = $this->get('em')
            ->find(Document::class, (int) $documentId);

        if ($document !== null) {
            // Assign document
            $this->assignation['document'] = $document;

            // Build form and handle it
            $fileForm = $this->buildFileForm();
            $fileForm->handleRequest($request);

            // Check if form is valid
            if ($fileForm->isValid()) {
                /** @var EntityManager $em */
                $em = $this->get('em');

                if (null !== $document->getRawDocument()) {
                    /** @var Document $cloneDocument */
                    $cloneDocument = clone $document;

                    // need to remove raw document BEFORE
                    // setting it to cloned document
                    $rawDocument = $document->getRawDocument();
                    $document->setRawDocument(null);
                    $em->flush();

                    $cloneDocument->setRawDocument($rawDocument);

                    /** @var Packages $packages */
                    $packages = $this->get('assetPackages');
                    $oldPath = $packages->getDocumentFilePath($cloneDocument);
                    $fs = new Filesystem();

                    /*
                     * Prefix document filename
                     */
                    $cloneDocument->setFilename('original_' . $cloneDocument->getFilename());
                    $newPath = $packages->getDocumentFilePath($cloneDocument);
                    $fs->rename(
                        $oldPath,
                        $newPath
                    );

                    $em->persist($cloneDocument);
                    $em->flush();
                }

                /** @var UploadedFile $uploadedFile */
                $uploadedFile = $fileForm->get('editDocument')->getData();
                /** @var DocumentFactory $documentFactory */
                $documentFactory = $this->get('document.factory');
                $documentFactory->setFile($uploadedFile);


                $documentFactory->updateDocument($document);
                $em->flush();

                /** @var Translator $translator */
                $translator = $this->get('translator');
                $msg = $translator->trans('document.%name%.updated', [
                    '%name%' => $document->getFilename(),
                ]);

                return new JsonResponse([
                    'message' => $msg,
                    'path' => $this->get('assetPackages')->getUrl($document->getRelativePath(), Packages::DOCUMENTS) . '?' . random_int(10, 999)
                ]);
            }

            // Create form view and assign it
            $this->assignation['file_form'] = $fileForm->createView();

            return $this->render('documents/adjust.html.twig', $this->assignation);
        }

        throw new ResourceNotFoundException();
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

        /** @var Document $document */
        $document = $this->get('em')->find(Document::class, (int) $documentId);

        if ($document !== null) {
            $this->assignation['document'] = $document;
            $this->assignation['rawDocument'] = $document->getRawDocument();
            /*
             * Handle main form
             */
            $form = $this->createForm(DocumentEditType::class, $document, [
                'referer' => $this->get('requestStack')->getCurrentRequest()->get('referer'),
                'assetPackages' => $this->get('assetPackages'),
                'document_platforms' => $this->get('document.platforms'),
            ]);
            $form->handleRequest($request);

            if ($form->isValid()) {
                try {
                    $this->get('em')->flush();
                    /*
                    * Update document file
                    * if present
                    */
                    if (null !== $newDocumentFile = $form->get('newDocument')->getData()) {
                        /** @var DocumentFactory $documentFactory */
                        $documentFactory = $this->get('document.factory');
                        $documentFactory->setFile($newDocumentFile);
                        $documentFactory->updateDocument($document);
                        $msg = $this->getTranslator()->trans('document.file.%name%.updated', [
                            '%name%' => $document->getFilename(),
                        ]);
                        $this->get('em')->flush();
                        $this->publishConfirmMessage($request, $msg);
                    }

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
                } catch (FileException $exception) {
                    $form->get('filename')->addError(new FormError($exception->getMessage()));
                }
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
            ->find(Document::class, (int) $documentId);

        if ($document !== null) {
            /** @var Packages $packages */
            $packages = $this->get('assetPackages');
            $documentPath = $packages->getDocumentFilePath($document);

            $this->assignation['document'] = $document;
            $this->assignation['thumbnailFormat'] = [
                'width' => 750,
                'controls' => true,
                'srcset' => [
                    [
                        'format' => [
                            'width' => 480,
                            'quality' => 80
                        ],
                        'rule' => '480w',
                    ],
                    [
                        'format' => [
                            'width' => 768,
                            'quality' => 80
                        ],
                        'rule' => '768w',
                    ],
                    [
                        'format' => [
                            'width' => 1400,
                            'quality' => 80
                        ],
                        'rule' => '1400w',
                    ],
                ],
                'sizes' => [
                    '(min-width: 1380px) 1200px',
                    '(min-width: 768px) 768px',
                    '(min-width: 480px) 480px',
                ],
            ];

            if ($this->get('interventionRequestSupportsWebP')) {
                $this->assignation['thumbnailFormat']['picture'] = true;
            }

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
            ->find(Document::class, (int) $documentId);

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

        $documentsIds = $request->get('documents', []);
        if (count($documentsIds) <= 0) {
            throw new ResourceNotFoundException('No selected documents to delete.');
        }

        $documents = $this->get('em')
            ->getRepository(Document::class)
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

        $documentsIds = $request->get('documents', []);
        if (count($documentsIds) <= 0) {
            throw new ResourceNotFoundException('No selected documents to download.');
        }

        $documents = $this->get('em')
            ->getRepository(Document::class)
            ->findBy([
                'id' => $documentsIds,
            ]);

        if ($documents !== null && count($documents) > 0) {
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
     * @param int $folderId
     *
     * @return Response
     */
    public function embedAction(Request $request, $folderId = null)
    {
        $this->validateAccessForRole('ROLE_ACCESS_DOCUMENTS');

        if (null !== $folderId &&
            $folderId > 0) {
            $folder = $this->get('em')
                ->find(Folder::class, (int) $folderId);

            $this->assignation['folder'] = $folder;
        }

        /*
         * Handle main form
         */
        $form = $this->createForm(DocumentEmbedType::class, null, [
            'document_platforms' => $this->get('document.platforms'),
        ]);
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
            } catch (APINeedsAuthentificationException $e) {
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
            ->find(Document::class, (int) $documentId);

        /** @var DocumentHandler $handler */
        $handler = $this->get('document.handler');
        $handler->setDocument($document);

        if ($document !== null) {
            return $handler->getDownloadResponse();
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
                ->find(Folder::class, (int) $folderId);

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
                            'document' => $documentModel->toArray(),
                        ], JsonResponse::HTTP_CREATED);
                    } else {
                        return $this->redirect($this->generateUrl('documentsHomePage', ['folderId' => $folderId]));
                    }
                } else {
                    $msg = $this->getTranslator()->trans('document.cannot_persist');
                    $this->publishErrorMessage($request, $msg);

                    if ($_format === 'json' || $request->isXmlHttpRequest()) {
                        throw $this->createNotFoundException($msg);
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
        $this->assignation['maxUploadSize'] = UploadedFile::getMaxFilesize() / 1024 / 1024;

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
        /** @var Document $document */
        $document = $this->get('em')->find(Document::class, (int) $documentId);

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
     * @return \Symfony\Component\Form\FormInterface
     */
    private function buildDeleteForm(Document $doc)
    {
        $defaults = [
            'documentId' => $doc->getId(),
        ];
        $builder = $this->createFormBuilder($defaults)
            ->add('documentId', HiddenType::class, [
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
     * @return \Symfony\Component\Form\FormInterface
     */
    private function buildBulkDeleteForm($documentsIds)
    {
        $defaults = [
            'checksum' => md5(serialize($documentsIds))
        ];
        $builder = $this->createFormBuilder($defaults, [
            'action' => '?' . http_build_query(['documents' => $documentsIds]),
        ])
            ->add('checksum', HiddenType::class, [
                'constraints' => [
                    new NotBlank(),
                ],
            ]);

        return $builder->getForm();
    }

    /**
     * @param array $documentsIds
     *
     * @return \Symfony\Component\Form\FormInterface
     */
    private function buildBulkDownloadForm($documentsIds)
    {
        $defaults = [
            'checksum' => md5(serialize($documentsIds))
        ];
        $builder = $this->createFormBuilder($defaults, [
            'action' => '?' . http_build_query(['documents' => $documentsIds]),
        ])
            ->add('checksum', HiddenType::class, [
                'constraints' => [
                    new NotBlank(),
                ],
            ]);

        return $builder->getForm();
    }

    /**
     * @return \Symfony\Component\Form\FormInterface
     */
    private function buildFileForm()
    {
        $defaults = [
            'editDocument' => null,
        ];

        $builder = $this->createFormBuilder($defaults)
            ->add('editDocument', FileType::class, [
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
     * @return \Symfony\Component\Form\FormInterface
     */
    private function buildUploadForm($folderId = null)
    {
        $builder = $this->createFormBuilder([], [
                'csrf_protection' => false,
            ])
            ->add('attachment', FileType::class, [
                'label' => 'choose.documents.to_upload',
                'constraints' => [
                    new File(),
                ],
            ]);

        if (null !== $folderId &&
            $folderId > 0) {
            $builder->add('folderId', HiddenType::class, [
                'data' => $folderId,
            ]);
        }

        return $builder->getForm();
    }

    /**
     * @return \Symfony\Component\Form\FormInterface
     */
    private function buildLinkFoldersForm()
    {
        $builder = $this->createNamedFormBuilder('folderForm')
            ->add('documentsId', HiddenType::class, [
                'attr' => ['class' => 'document-id-bulk-folder'],
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('folderPaths', TextType::class, [
                'label' => false,
                'attr' => [
                    'class' => 'rz-folder-autocomplete',
                    'placeholder' => 'list.folders.to_link',
                ],
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('submitFolder', SubmitType::class, [
                'label' => false,
                'attr' => [
                    'class' => 'uk-button uk-button-primary',
                    'title' => 'link.folders',
                    'data-uk-tooltip' => "{animation:true}",
                ],
            ])
            ->add('submitUnfolder', SubmitType::class, [
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
                ->getRepository(Document::class)
                ->findBy([
                    'id' => $documentsIds,
                ]);

            $folderPaths = explode(',', $data['folderPaths']);
            $folderPaths = array_filter($folderPaths);

            foreach ($folderPaths as $path) {
                /** @var Folder $folder */
                $folder = $this->get('em')
                    ->getRepository(Folder::class)
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
                ->getRepository(Document::class)
                ->findBy([
                    'id' => $documentsIds,
                ]);

            $folderPaths = explode(',', $data['folderPaths']);
            $folderPaths = array_filter($folderPaths);

            foreach ($folderPaths as $path) {
                /** @var Folder $folder */
                $folder = $this->get('em')
                    ->getRepository(Folder::class)
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
            $tmpFileName = tempnam(sys_get_temp_dir(), "rzdocs_");
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
     * @return DocumentInterface
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

            /*
             * Use empty constructor.
             */
            /** @var AbstractEmbedFinder $finder */
            $finder = new $class('', false);

            if ($finder instanceof YoutubeEmbedFinder) {
                $finder->setKey($this->get('settingsBag')->get('google_server_id'));
            }
            if ($finder instanceof SoundcloudEmbedFinder) {
                $finder->setKey($this->get('settingsBag')->get('soundcloud_client_id'));
            }
            $finder->setEmbedId($data['embedId']);

            $document = $finder->createDocumentFromFeed($this->get('em'), $this->get('document.factory'));
            if (null !== $document &&
                null !== $folderId &&
                $folderId > 0) {
                /** @var Folder $folder */
                $folder = $this->get('em')->find(Folder::class, (int) $folderId);

                $document->addFolder($folder);
                $folder->addDocument($document);
            }

            $this->get('em')->flush();

            return $document;
        } else {
            throw new \RuntimeException("bad.request", 1);
        }
    }

    /**
     * Download a random document.
     *
     * @param int $folderId
     *
     * @return DocumentInterface
     * @throws \Exception
     * @throws \RZ\Roadiz\Core\Exceptions\EntityAlreadyExistsException
     */
    public function randomDocument($folderId = null)
    {
        $finder = new SplashbasePictureFinder();
        $document = $finder->createDocumentFromFeed($this->get('em'), $this->get('document.factory'));

        if (null !== $document &&
            null !== $folderId &&
            $folderId > 0) {
            /** @var Folder $folder */
            $folder = $this->get('em')->find(Folder::class, (int) $folderId);

            $document->addFolder($folder);
            $folder->addDocument($document);
        }
        $this->get('em')->flush();

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
            /** @var DocumentHandler $handler */
            $handler = $this->get('document.handler');
            $handler->setDocument($document);

            if ($data['private'] === true) {
                $handler->makePrivate();
            } else {
                $handler->makePublic();
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
                ->find(Folder::class, (int) $folderId);
        }

        if (!empty($data['attachment'])) {
            $uploadedFile = $data['attachment']->getData();

            /** @var DocumentFactory $documentFactory */
            $documentFactory = $this->get('document.factory');
            $documentFactory->setFile($uploadedFile);
            $documentFactory->setFolder($folder);

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
            ->getRepository(Document::class)
            ->findAllUnused();
        $this->assignation['filters'] = [
            'itemCount' => count($this->assignation['documents']),
            'itemPerPage' => false,
        ];
        $this->assignation['thumbnailFormat'] = $this->thumbnailFormat;

        return $this->render('documents/list.html.twig', $this->assignation);
    }
}
