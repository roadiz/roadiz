<?php
declare(strict_types=1);

namespace Themes\Rozier\Controllers\Documents;

use Doctrine\ORM\EntityManager;
use GuzzleHttp\Exception\RequestException;
use RZ\Roadiz\Core\Entities\AttributeDocuments;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Entities\Folder;
use RZ\Roadiz\Core\Entities\TagTranslationDocuments;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Events\DocumentCreatedEvent;
use RZ\Roadiz\Core\Events\DocumentDeletedEvent;
use RZ\Roadiz\Core\Events\DocumentInFolderEvent;
use RZ\Roadiz\Core\Events\DocumentOutFolderEvent;
use RZ\Roadiz\Core\Events\DocumentUpdatedEvent;
use RZ\Roadiz\Core\Exceptions\APINeedsAuthentificationException;
use RZ\Roadiz\Core\Exceptions\EntityAlreadyExistsException;
use RZ\Roadiz\Core\Handlers\DocumentHandler;
use RZ\Roadiz\Core\ListManagers\QueryBuilderListManager;
use RZ\Roadiz\Core\Models\DocumentInterface;
use RZ\Roadiz\Core\Repositories\DocumentRepository;
use RZ\Roadiz\Utils\Asset\Packages;
use RZ\Roadiz\Utils\Document\DocumentFactory;
use RZ\Roadiz\Utils\MediaFinders\AbstractEmbedFinder;
use RZ\Roadiz\Utils\MediaFinders\RandomImageFinder;
use RZ\Roadiz\Utils\MediaFinders\SoundcloudEmbedFinder;
use RZ\Roadiz\Utils\MediaFinders\SplashbasePictureFinder;
use RZ\Roadiz\Utils\MediaFinders\YoutubeEmbedFinder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\ClickableInterface;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Themes\Rozier\Forms\DocumentEditType;
use Themes\Rozier\Forms\DocumentEmbedType;
use Themes\Rozier\Models\DocumentModel;
use Themes\Rozier\RozierApp;
use Themes\Rozier\Utils\SessionListFilters;

/**
 * @package Themes\Rozier\Controllers\Documents
 */
class DocumentsController extends RozierApp
{
    protected $thumbnailFormat = [
        'quality' => 50,
        'fit' => '128x128',
        'sharpen' => 5,
        'inline' => false,
        'picture' => true,
        'loading' => 'lazy',
    ];

    /**
     * @param Request $request
     * @param int|null    $folderId
     *
     * @return RedirectResponse|Response
     */
    public function indexAction(Request $request, ?int $folderId = null)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_DOCUMENTS');

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
                ->find(Folder::class, $folderId);

            $prefilters['folders'] = [$folder];
            $this->assignation['folder'] = $folder;
        }

        if ($request->query->has('type') &&
            $request->query->get('type', '') !== '') {
            $prefilters['mimeType'] = trim($request->query->get('type', ''));
            $this->assignation['mimeType'] = trim($request->query->get('type', ''));
        }

        if ($request->query->has('embedPlatform') &&
            $request->query->get('embedPlatform', '') !== '') {
            $prefilters['embedPlatform'] = trim($request->query->get('embedPlatform', ''));
            $this->assignation['embedPlatform'] = trim($request->query->get('embedPlatform', ''));
        }
        $this->assignation['availablePlatforms'] = $this->get('document.platforms');

        /*
         * Handle bulk folder form
         */
        $joinFolderForm = $this->buildLinkFoldersForm();
        $joinFolderForm->handleRequest($request);
        if ($joinFolderForm->isSubmitted() && $joinFolderForm->isValid()) {
            $data = $joinFolderForm->getData();
            $submitFolder = $joinFolderForm->get('submitFolder');
            $submitUnfolder = $joinFolderForm->get('submitUnfolder');
            if ($submitFolder instanceof ClickableInterface && $submitFolder->isClicked()) {
                $msg = $this->joinFolder($data);
            } elseif ($submitUnfolder instanceof ClickableInterface && $submitUnfolder->isClicked()) {
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

        return $this->render($this->getListingTemplate($request), $this->assignation);
    }

    /**
     * @param Request $request
     * @param int $documentId
     * @return JsonResponse|Response
     */
    public function adjustAction(Request $request, int $documentId)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_DOCUMENTS');

        /** @var Document $document */
        $document = $this->get('em')->find(Document::class, $documentId);

        if ($document !== null && $document->isLocal()) {
            // Assign document
            $this->assignation['document'] = $document;

            // Build form and handle it
            $fileForm = $this->buildFileForm();
            $fileForm->handleRequest($request);

            // Check if form is valid
            if ($fileForm->isSubmitted() && $fileForm->isValid()) {
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
                    $cloneDocument->setFilename('original_' . $cloneDocument);
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
                    '%name%' => (string) $document,
                ]);

                return new JsonResponse([
                    'message' => $msg,
                    'path' => $this->get('assetPackages')->getUrl($document->getRelativePath(), Packages::DOCUMENTS) . '?' . random_int(10, 999),
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
    public function editAction(Request $request, int $documentId)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_DOCUMENTS');

        /** @var Document|null $document */
        $document = $this->get('em')->find(Document::class, $documentId);

        if ($document !== null) {
            /*
             * Handle main form
             */
            $form = $this->createForm(DocumentEditType::class, $document, [
                'referer' => $this->get('requestStack')->getCurrentRequest()->get('referer'),
                'document_platforms' => $this->get('document.platforms'),
            ]);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
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
                            '%name%' => (string) $document,
                        ]);
                        $this->get('em')->flush();
                        $this->publishConfirmMessage($request, $msg);
                    }

                    $msg = $this->getTranslator()->trans('document.%name%.updated', [
                       '%name%' => (string) $document,
                    ]);
                    $this->publishConfirmMessage($request, $msg);
                    $this->get("dispatcher")->dispatch(
                        new DocumentUpdatedEvent($document)
                    );
                    $this->get('em')->flush();

                    $routeParams = ['documentId' => $document->getId()];

                    if ($form->get('referer')->getData()) {
                        $routeParams = array_merge($routeParams, [
                           'referer' => $form->get('referer')->getData(),
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

            $this->assignation['document'] = $document;
            $this->assignation['rawDocument'] = $document->getRawDocument();
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
    public function previewAction(Request $request, int $documentId)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_DOCUMENTS');

        /** @var Document|null $document */
        $document = $this->get('em')->find(Document::class, $documentId);

        if ($document !== null) {
            $this->assignation['document'] = $document;
            $this->assignation['thumbnailFormat'] = [
                'width' => 750,
                'controls' => true,
                'srcset' => [
                    [
                        'format' => [
                            'width' => 480,
                            'quality' => 80,
                        ],
                        'rule' => '480w',
                    ],
                    [
                        'format' => [
                            'width' => 768,
                            'quality' => 80,
                        ],
                        'rule' => '768w',
                    ],
                    [
                        'format' => [
                            'width' => 1400,
                            'quality' => 80,
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

            $this->assignation['infos'] = [];
            if ($document->isProcessable() || $document->isSvg()) {
                $this->assignation['infos']['width'] = $document->getImageWidth() . 'px';
                $this->assignation['infos']['height'] = $document->getImageHeight() . 'px';
            }
            if ($document->getMediaDuration() > 0) {
                $this->assignation['infos']['duration'] = $document->getMediaDuration() . ' sec';
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
    public function deleteAction(Request $request, int $documentId)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_DOCUMENTS_DELETE');

        /** @var Document|null $document */
        $document = $this->get('em')->find(Document::class, $documentId);

        if ($document !== null) {
            $this->assignation['document'] = $document;
            $form = $this->buildDeleteForm($document);
            $form->handleRequest($request);

            if ($form->isSubmitted() &&
                $form->isValid() &&
                $form->getData()['documentId'] == $document->getId()) {
                try {
                    $this->get("dispatcher")->dispatch(
                        new DocumentDeletedEvent($document)
                    );
                    $this->get('em')->remove($document);
                    $this->get('em')->flush();
                    $msg = $this->getTranslator()->trans('document.%name%.deleted', [
                        '%name%' => (string) $document
                    ]);
                    $this->publishConfirmMessage($request, $msg);
                } catch (\Exception $e) {
                    $msg = $this->getTranslator()->trans('document.%name%.cannot_delete', [
                        '%name%' => (string) $document
                    ]);
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
        $this->denyAccessUnlessGranted('ROLE_ACCESS_DOCUMENTS_DELETE');

        $documentsIds = $request->get('documents', []);
        if (count($documentsIds) <= 0) {
            throw new ResourceNotFoundException('No selected documents to delete.');
        }

        /** @var array<Document> $documents */
        $documents = $this->get('em')
            ->getRepository(Document::class)
            ->findBy([
                'id' => $documentsIds,
            ]);

        if (count($documents) > 0) {
            $this->assignation['documents'] = $documents;
            $form = $this->buildBulkDeleteForm($documentsIds);

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                foreach ($documents as $document) {
                    $this->get('em')->remove($document);
                    $msg = $this->getTranslator()->trans(
                        'document.%name%.deleted',
                        ['%name%' => (string) $document]
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
        $this->denyAccessUnlessGranted('ROLE_ACCESS_DOCUMENTS');

        $documentsIds = $request->get('documents', []);
        if (count($documentsIds) <= 0) {
            throw new ResourceNotFoundException('No selected documents to download.');
        }

        /** @var array<Document> $documents */
        $documents = $this->get('em')
            ->getRepository(Document::class)
            ->findBy([
                'id' => $documentsIds,
            ]);

        if (count($documents) > 0) {
            $this->assignation['documents'] = $documents;
            $form = $this->buildBulkDownloadForm($documentsIds);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
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
     * @param int|null $folderId
     *
     * @return Response
     */
    public function embedAction(Request $request, ?int $folderId = null)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_DOCUMENTS');

        if (null !== $folderId && $folderId > 0) {
            $folder = $this->get('em')->find(Folder::class, $folderId);

            $this->assignation['folder'] = $folder;
        }

        /*
         * Handle main form
         */
        $form = $this->createForm(DocumentEmbedType::class, null, [
            'document_platforms' => $this->get('document.platforms'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $document = $this->embedDocument($form->getData(), $folderId);

                if (is_iterable($document)) {
                    foreach ($document as $singleDocument) {
                        $msg = $this->getTranslator()->trans('document.%name%.uploaded', [
                            '%name%' => (string) $singleDocument,
                        ]);
                        $this->publishConfirmMessage($request, $msg);
                        $this->get("dispatcher")->dispatch(
                            new DocumentCreatedEvent($singleDocument)
                        );
                    }
                } else {
                    $msg = $this->getTranslator()->trans('document.%name%.uploaded', [
                        '%name%' => (string) $document,
                    ]);
                    $this->publishConfirmMessage($request, $msg);
                    $this->get("dispatcher")->dispatch(
                        new DocumentCreatedEvent($document)
                    );
                }
                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                return $this->redirect($this->generateUrl('documentsHomePage', ['folderId' => $folderId]));
            } catch (RequestException $e) {
                $this->get('logger')->error($e->getRequest()->getUri() . ' failed.');
                if (null !== $e->getResponse() && in_array($e->getResponse()->getStatusCode(), [401, 403, 404])) {
                    $form->addError(new FormError(
                        $this->getTranslator()->trans('document.media_not_found_or_private')
                    ));
                } else {
                    $form->addError(new FormError($this->getTranslator()->trans($e->getMessage())));
                }
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
     * @param int|null $folderId
     *
     * @return Response
     */
    public function randomAction(Request $request, ?int $folderId = null)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_DOCUMENTS');

        try {
            $document = $this->randomDocument($folderId);

            $msg = $this->getTranslator()->trans('document.%name%.uploaded', [
                '%name%' => (string) $document,
            ]);
            $this->publishConfirmMessage($request, $msg);

            $this->get("dispatcher")->dispatch(
                new DocumentCreatedEvent($document)
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
    public function downloadAction(Request $request, int $documentId)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_DOCUMENTS');

        /** @var Document|null $document */
        $document = $this->get('em')->find(Document::class, $documentId);

        if ($document !== null) {
            /** @var DocumentHandler $handler */
            $handler = $this->get('document.handler');
            $handler->setDocument($document);

            return $handler->getDownloadResponse();
        }

        throw new ResourceNotFoundException();
    }

    /**
     * @param Request $request
     * @param int|null $folderId
     * @param string $_format
     * @return Response
     */
    public function uploadAction(Request $request, ?int $folderId = null, string $_format = 'html')
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_DOCUMENTS');

        if (null !== $folderId && $folderId > 0) {
            $folder = $this->get('em')->find(Folder::class, $folderId);

            $this->assignation['folder'] = $folder;
        }

        /*
         * Handle main form
         */
        $form = $this->buildUploadForm($folderId);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isSubmitted() && $form->isValid()) {
                $document = $this->uploadDocument($form, $folderId);

                if (false !== $document) {
                    $msg = $this->getTranslator()->trans('document.%name%.uploaded', [
                        '%name%' => (string) $document,
                    ]);
                    $this->publishConfirmMessage($request, $msg);

                    $this->get("dispatcher")->dispatch(
                        new DocumentCreatedEvent($document)
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
    public function usageAction(Request $request, int $documentId)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_DOCUMENTS');
        /** @var Document|null $document */
        $document = $this->get('em')->find(Document::class, $documentId);

        if ($document !== null) {
            $this->assignation['document'] = $document;
            $this->assignation['usages'] = $document->getNodesSourcesByFields();
            $this->assignation['attributes'] = $document->getAttributeDocuments()
                ->map(function (AttributeDocuments $attributeDocument) {
                    return $attributeDocument->getAttribute();
                });
            $this->assignation['tags'] = $document->getTagTranslations()
                ->map(function (TagTranslationDocuments $tagTranslationDocuments) {
                    return $tagTranslationDocuments->getTagTranslation()->getTag();
                });

            return $this->render('documents/usage.html.twig', $this->assignation);
        }

        throw new ResourceNotFoundException();
    }

    /**
     * See unused documents.
     *
     * @param  Request $request
     * @return Response
     */
    public function unusedAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_DOCUMENTS');

        $this->assignation['orphans'] = true;
        /** @var DocumentRepository $documentRepository */
        $documentRepository = $this->get('em')
            ->getRepository(Document::class);

        $listManager = new QueryBuilderListManager(
            $request,
            $documentRepository->getAllUnusedQueryBuilder(),
            'd'
        );
        $listManager->setItemPerPage(static::DEFAULT_ITEM_PER_PAGE);

        /*
         * Stored in session
         */
        $sessionListFilter = new SessionListFilters('unused_documents_item_per_page');
        $sessionListFilter->handleItemPerPage($request, $listManager);

        $listManager->handle();

        $this->assignation['filters'] = $listManager->getAssignation();
        $this->assignation['no_sorting'] = true;
        $this->assignation['documents'] = $listManager->getEntities();
        $this->assignation['thumbnailFormat'] = $this->thumbnailFormat;

        return $this->render('documents/list-table.html.twig', $this->assignation);
    }

    /**
     * @param Document $doc
     *
     * @return FormInterface
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
                    new NotNull(),
                    new NotBlank(),
                ],
            ]);

        return $builder->getForm();
    }
    /**
     * @param array $documentsIds
     *
     * @return FormInterface
     */
    private function buildBulkDeleteForm($documentsIds)
    {
        $defaults = [
            'checksum' => md5(serialize($documentsIds)),
        ];
        $builder = $this->createFormBuilder($defaults, [
            'action' => '?' . http_build_query(['documents' => $documentsIds]),
        ])
            ->add('checksum', HiddenType::class, [
                'constraints' => [
                    new NotNull(),
                    new NotBlank(),
                ],
            ]);

        return $builder->getForm();
    }

    /**
     * @param array $documentsIds
     *
     * @return FormInterface
     */
    private function buildBulkDownloadForm($documentsIds)
    {
        $defaults = [
            'checksum' => md5(serialize($documentsIds)),
        ];
        $builder = $this->createFormBuilder($defaults, [
            'action' => '?' . http_build_query(['documents' => $documentsIds]),
        ])
            ->add('checksum', HiddenType::class, [
                'constraints' => [
                    new NotNull(),
                    new NotBlank(),
                ],
            ]);

        return $builder->getForm();
    }

    /**
     * @return FormInterface
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
                    new File(),
                ],
            ]);

        return $builder->getForm();
    }

    /**
     * @param int $folderId
     *
     * @return FormInterface
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
     * @return FormInterface
     */
    private function buildLinkFoldersForm()
    {
        $builder = $this->createNamedFormBuilder('folderForm')
            ->add('documentsId', HiddenType::class, [
                'attr' => ['class' => 'document-id-bulk-folder'],
                'constraints' => [
                    new NotNull(),
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
                    new NotNull(),
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
                    new DocumentInFolderEvent($document)
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
                    new DocumentOutFolderEvent($document)
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
                if ($document->isLocal()) {
                    $documentPath = $this->get('assetPackages')->getDocumentFilePath($document);
                    $zip->addFile($documentPath, $document->getFilename());
                }
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
     * @param array    $data
     * @param int|null $folderId
     *
     * @return DocumentInterface|array<DocumentInterface>
     * @throws \Exception
     * @throws EntityAlreadyExistsException
     */
    private function embedDocument($data, ?int $folderId = null)
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
            return $this->createDocumentFromFinder($finder, $folderId);
        } else {
            throw new \RuntimeException("bad.request", 1);
        }
    }

    /**
     * Download a random document.
     *
     * @param int|null $folderId
     *
     * @return DocumentInterface
     * @throws \Exception
     * @throws EntityAlreadyExistsException
     */
    private function randomDocument(?int $folderId = null)
    {
        return $this->createDocumentFromFinder($this->get(RandomImageFinder::class), $folderId);
    }

    /**
     * @param AbstractEmbedFinder $finder
     * @param int|null            $folderId
     *
     * @return array|DocumentInterface
     */
    private function createDocumentFromFinder(AbstractEmbedFinder $finder, ?int $folderId = null)
    {
        $document = $finder->createDocumentFromFeed($this->get('em'), $this->get('document.factory'));

        if (null !== $document && null !== $folderId && $folderId > 0) {
            /** @var Folder $folder */
            $folder = $this->get('em')->find(Folder::class, (int) $folderId);

            if (is_iterable($document)) {
                /** @var DocumentInterface $singleDocument */
                foreach ($document as $singleDocument) {
                    $singleDocument->addFolder($folder);
                    $folder->addDocument($singleDocument);
                }
            } else {
                $document->addFolder($folder);
                $folder->addDocument($document);
            }
        }
        $this->get('em')->flush();

        return $document;
    }

    /**
     * Handle upload form data to create a Document.
     *
     * @param FormInterface $data
     * @param int|null      $folderId
     *
     * @return bool|DocumentInterface
     */
    private function uploadDocument($data, ?int $folderId = null)
    {
        $folder = null;
        if (null !== $folderId && $folderId > 0) {
            /** @var Folder $folder */
            $folder = $this->get('em')->find(Folder::class, $folderId);
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

    private function getListingTemplate(Request $request): string
    {
        if ($request->query->get('list') === '1') {
            return 'documents/list-table.html.twig';
        }
        return 'documents/list.html.twig';
    }
}
