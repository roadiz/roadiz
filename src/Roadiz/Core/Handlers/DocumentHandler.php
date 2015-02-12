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
     * Remove document assets and db row.
     *
     * @return boolean
     */
    public function removeWithAssets()
    {
        Kernel::getService('em')->remove($this->document);

        if (file_exists($this->document->getAbsolutePath())) {
            if (unlink($this->document->getAbsolutePath())) {
                $this->cleanParentDirectory();
                Kernel::getService('em')->flush();

                return true;
            } else {
                throw new \Exception("document.cannot_delete", 1);
            }
        } else {
            /*
             * Only remove from DB
             * and check directory
             */
            $this->cleanParentDirectory();
            Kernel::getService('em')->flush();

            return true;
        }
    }

    /**
     * Remove document directory if there is no other file in it.
     *
     * @return boolean
     */
    public function cleanParentDirectory()
    {
        $dir = dirname($this->document->getAbsolutePath());

        if (file_exists($dir)) {
            $finder = new Finder();
            $finder->files()->in($dir);

            if (count($finder) <= 0) {
                /*
                 * Directory is empty
                 */
                if (rmdir($dir)) {
                    return true;
                } else {
                    throw new \Exception("document.cannot_delete.parent_folder", 1);
                }
            }
        }

        return false;
    }
}
