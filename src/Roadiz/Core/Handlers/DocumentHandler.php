<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Handlers;

use Doctrine\Persistence\ObjectManager;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Entities\DocumentTranslation;
use RZ\Roadiz\Core\Entities\Folder;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Repositories\FolderRepository;
use RZ\Roadiz\Utils\Asset\Packages;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * Handle operations with documents entities.
 */
class DocumentHandler extends AbstractHandler
{
    protected ?Document $document = null;
    protected Packages $packages;

    /**
     * Create a new document handler with document to handle.
     *
     * @param ObjectManager $objectManager
     * @param Packages $packages
     */
    public function __construct(ObjectManager $objectManager, Packages $packages)
    {
        parent::__construct($objectManager);
        $this->packages = $packages;
    }

    /**
     * Make current document private moving its file
     * to the secured /files/private folder.
     *
     * You must explicitly call flush after this method.
     * @deprecated USe DocumentLifeCycle events
     */
    public function makePrivate()
    {
        if (null === $this->document) {
            throw new \BadMethodCallException('Document is null');
        }
        $documentPublicPath = $this->packages->getPublicFilesPath($this->document->getRelativePath());
        $documentPrivatePath = $this->packages->getPrivateFilesPath($this->document->getRelativePath());

        if (!$this->document->isPrivate()) {
            $fs = new Filesystem();

            if ($fs->exists($documentPublicPath)) {
                /*
                 * Create destination folder if not exist
                 */
                if (!$fs->exists(dirname($documentPrivatePath))) {
                    $fs->mkdir(dirname($documentPrivatePath));
                }
                $fs->rename(
                    $documentPublicPath,
                    $documentPrivatePath
                );
                $this->document->setPrivate(true);

                /*
                 * Bubble privatisation to raw document if available.
                 */
                if (null !== $this->document->getRawDocument() && !$this->document->getRawDocument()->isPrivate()) {
                    $rawHandler = new DocumentHandler($this->objectManager, $this->packages);
                    $rawHandler->setDocument($this->document->getRawDocument());
                    $rawHandler->makePrivate();
                }
            } else {
                throw new \RuntimeException("Can’t make private a document file which does not exist.", 1);
            }
        } else {
            throw new \RuntimeException("Can’t make private an already private document.", 1);
        }
    }

    /**
     * Make current document public moving off its file
     * from the secured /files/private folder into /files folder.
     *
     * You must explicitly call flush after this method.
     * @deprecated Use DocumentLifeCycle events
     */
    public function makePublic()
    {
        if (null === $this->document) {
            throw new \BadMethodCallException('Document is null');
        }
        $documentPublicPath = $this->packages->getPublicFilesPath($this->document->getRelativePath());
        $documentPrivatePath = $this->packages->getPrivateFilesPath($this->document->getRelativePath());

        if ($this->document->isPrivate()) {
            $fs = new Filesystem();

            if ($fs->exists($documentPrivatePath)) {
                /*
                 * Create destination folder if not exist
                 */
                if (!$fs->exists(dirname($documentPublicPath))) {
                    $fs->mkdir(dirname($documentPublicPath));
                }

                $fs->rename(
                    $documentPrivatePath,
                    $documentPublicPath
                );
                $this->document->setPrivate(false);

                /*
                 * Bubble un-privatisation to raw document if available.
                 */
                if (null !== $this->document->getRawDocument() &&
                    $this->document->getRawDocument()->isPrivate()) {
                    $rawHandler = new DocumentHandler($this->objectManager, $this->packages);
                    $rawHandler->setDocument($this->document->getRawDocument());
                    $rawHandler->makePublic();
                }
            } else {
                throw new \RuntimeException("Can’t make public a document file which does not exist.", 1);
            }
        } else {
            throw new \RuntimeException("Can’t make public an already public document.", 1);
        }
    }

    /**
     * Get a Response object to force download document.
     * This method works for both private and public documents.
     *
     * @return Response
     */
    public function getDownloadResponse()
    {
        $fs = new Filesystem();
        $documentPath = $this->packages->getDocumentFilePath($this->document);

        if ($fs->exists($documentPath)) {
            $response =  new BinaryFileResponse($documentPath, Response::HTTP_OK, [], false);
            $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT);

            return $response;
        }

        throw new ResourceNotFoundException();
    }

    /**
     * Return documents folders with the same translation as
     * current document.
     *
     * @param Translation|null $translation
     * @return array
     */
    public function getFolders(Translation $translation = null): array
    {
        /** @var FolderRepository $repository */
        $repository = $this->objectManager->getRepository(Folder::class);
        if (null !== $translation) {
            return $repository->findByDocumentAndTranslation($this->document, $translation);
        }

        $docTranslation = $this->document->getDocumentTranslations()->first();
        if (null !== $docTranslation &&
            $docTranslation instanceof DocumentTranslation) {
            return $repository->findByDocumentAndTranslation($this->document, $docTranslation->getTranslation());
        }

        return $repository->findByDocumentAndTranslation($this->document);
    }

    public function getDocument(): ?Document
    {
        return $this->document;
    }

    /**
     * @param Document $document
     * @return DocumentHandler
     */
    public function setDocument(Document $document)
    {
        $this->document = $document;
        return $this;
    }
}
