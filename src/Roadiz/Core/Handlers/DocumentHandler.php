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

use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Kernel;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

/**
 * Handle operations with documents entities.
 */
class DocumentHandler
{
    protected $document;

    /**
     * Create a new document handler with document to handle.
     *
     * @param Document $document
     */
    public function __construct(Document $document)
    {
        $this->document = $document;
    }

    /**
     * Make current document private moving its file
     * to the secured /files/private folder.
     */
    public function makePrivate()
    {
        if (!$this->document->isPrivate()) {
            $fs = new Filesystem();

            if ($fs->exists($this->document->getPublicAbsolutePath())) {
                /*
                 * Create destination folder if not exist
                 */
                if (!$fs->exists(dirname($this->document->getPrivateAbsolutePath()))) {
                    $fs->mkdir(dirname($this->document->getPrivateAbsolutePath()));
                }
                $fs->rename(
                    $this->document->getPublicAbsolutePath(),
                    $this->document->getPrivateAbsolutePath()
                );
                $this->document->setPrivate(true);
                Kernel::getService('em')->flush();
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
     */
    public function makePublic()
    {
        if ($this->document->isPrivate()) {
            $fs = new Filesystem();

            if ($fs->exists($this->document->getPrivateAbsolutePath())) {
                /*
                 * Create destination folder if not exist
                 */
                if (!$fs->exists(dirname($this->document->getPublicAbsolutePath()))) {
                    $fs->mkdir(dirname($this->document->getPublicAbsolutePath()));
                }

                $fs->rename(
                    $this->document->getPrivateAbsolutePath(),
                    $this->document->getPublicAbsolutePath()
                );
                $this->document->setPrivate(false);
                Kernel::getService('em')->flush();
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
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function getDownloadResponse()
    {
        $fs = new Filesystem();
        if ($fs->exists($this->document->getAbsolutePath())) {
            $response = new Response();
            // Set headers
            $response->headers->set('Cache-Control', 'private');
            $response->headers->set('Content-type', mime_content_type($this->document->getAbsolutePath()));
            $response->headers->set('Content-Disposition', 'attachment; filename="' . basename($this->document->getAbsolutePath()) . '";');
            $response->headers->set('Content-length', filesize($this->document->getAbsolutePath()));
            // Send headers before outputting anything
            $response->sendHeaders();
            // Set content
            $response->setContent(readfile($this->document->getAbsolutePath()));

            return $response;
        } else {
            return null;
        }
    }
}
