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
 * @file DocumentFactory.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */
namespace RZ\Roadiz\Utils\Document;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Entities\Folder;
use RZ\Roadiz\Core\Events\DocumentEvents;
use RZ\Roadiz\Core\Events\FilterDocumentEvent;
use RZ\Roadiz\Utils\StringHandler;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Create documents from UploadedFile.
 *
 * Factory methods do not flush, only persist in order to use it in loops.
 *
 * @package RZ\Roadiz\Utils\Document
 */
class DocumentFactory
{
    /**
     * @var UploadedFile
     */
    private $uploadedFile;
    /**
     * @var Folder
     */
    private $folder;

    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var EntityManager
     */
    private $em;
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * DocumentFactory constructor.
     * @param UploadedFile $uploadedFile
     * @param EntityManager $em
     * @param EventDispatcherInterface $dispatcher
     * @param Folder $folder
     * @param LoggerInterface $logger
     */
    public function __construct(
        UploadedFile $uploadedFile,
        EntityManager $em,
        EventDispatcherInterface $dispatcher,
        Folder $folder = null,
        LoggerInterface $logger = null
    ) {
        $this->uploadedFile = $uploadedFile;
        $this->folder = $folder;
        $this->logger = $logger;
        $this->em = $em;
        $this->dispatcher = $dispatcher;

        if (null === $this->logger) {
            $this->logger = new NullLogger();
        }
    }

    /**
     * Special case for SVG without XML statement.
     *
     * @param Document $document
     */
    protected function parseSvgMimeType(Document $document)
    {
        if (($document->getMimeType() == "text/plain" ||
                $document->getMimeType() == 'text/html') &&
            preg_match('#\.svg$#', $this->uploadedFile->getClientOriginalName())) {
            $this->logger->debug('Uploaded a SVG without xml declaration. Presuming it’s a valid SVG file.');
            $document->setMimeType('image/svg+xml');
        }
    }

    /**
     * Create a document from UploadedFile, Be careful, this method does not flush, only
     * persists current Document.
     *
     * @return null|Document
     */
    public function getDocument()
    {
        if ($this->uploadedFile !== null &&
            $this->uploadedFile->getError() == UPLOAD_ERR_OK &&
            $this->uploadedFile->isValid()) {
            try {
                $document = new Document();
                $document->setFilename($this->uploadedFile->getClientOriginalName());
                $document->setMimeType($this->uploadedFile->getMimeType());
                $this->em->persist($document);

                $this->parseSvgMimeType($document);

                if (null !== $this->folder) {
                    $document->addFolder($this->folder);
                    $this->folder->addDocument($document);
                }

                $this->uploadedFile->move(
                    Document::getFilesFolder() . '/' . $document->getFolder(),
                    $document->getFilename()
                );

                if ($document->isImage()) {
                    $this->dispatcher->dispatch(
                        DocumentEvents::DOCUMENT_IMAGE_UPLOADED,
                        new FilterDocumentEvent($document)
                    );
                }

                return $document;
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
                return null;
            }
        }

        return null;
    }

    /**
     * Updates a document from UploadedFile, Be careful, this method does not flush.
     *
     * @param Document $document
     * @return Document
     */
    public function updateDocument(Document $document)
    {
        $fs = new Filesystem();

        if ($this->uploadedFile !== null &&
            $this->uploadedFile->getError() == UPLOAD_ERR_OK &&
            $this->uploadedFile->isValid()) {
            /*
             * In case file already exists
             */
            if ($fs->exists($document->getAbsolutePath())) {
                $fs->remove($document->getAbsolutePath());
            }

            if (StringHandler::cleanForFilename($this->uploadedFile->getClientOriginalName()) == $document->getFilename()) {
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

            $document->setFilename($this->uploadedFile->getClientOriginalName());
            $document->setMimeType($this->uploadedFile->getMimeType());
            $this->parseSvgMimeType($document);

            $this->uploadedFile->move(
                Document::getFilesFolder() . '/' . $document->getFolder(),
                $document->getFilename()
            );

            if ($document->isImage()) {
                $this->dispatcher->dispatch(
                    DocumentEvents::DOCUMENT_IMAGE_UPLOADED,
                    new FilterDocumentEvent($document)
                );
            }
        }
        return $document;
    }
}
