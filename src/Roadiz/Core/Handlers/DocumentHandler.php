<?php
/**
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
 * @file DocumentHandler.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Handlers;

use Doctrine\Common\Persistence\ObjectManager;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Entities\DocumentTranslation;
use RZ\Roadiz\Core\Entities\Folder;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Repositories\FolderRepository;
use RZ\Roadiz\Utils\Asset\Packages;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;

/**
 * Handle operations with documents entities.
 */
class DocumentHandler extends AbstractHandler
{
    /**
     * @var Document
     */
    protected $document;

    /**
     * @var Packages
     */
    protected $packages;

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
     */
    public function makePrivate()
    {
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
     */
    public function makePublic()
    {
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
     *
     * This method works for both private and public documents.
     *
     * **Be careful, this method will send headers.**
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getDownloadResponse()
    {
        $fs = new Filesystem();

        $documentPath = $this->packages->getDocumentFilePath($this->document);

        if ($fs->exists($documentPath)) {
            $response = new Response();
            // Set headers
            $response->headers->set('Cache-Control', 'private');
            $response->headers->set('Content-type', mime_content_type($documentPath));
            $response->headers->set('Content-Disposition', 'attachment; filename="' . basename($documentPath) . '";');
            $response->headers->set('Content-length', filesize($documentPath));
            // Send headers before outputting anything
            $response->sendHeaders();
            // Set content
            $response->setContent(readfile($documentPath));

            return $response;
        } else {
            return null;
        }
    }

    /**
     * Return documents folders with the same translation as
     * current document.
     *
     * @param Translation $translation
     * @return array
     */
    public function getFolders(Translation $translation = null)
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

    /**
     * @return Document
     */
    public function getDocument()
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
