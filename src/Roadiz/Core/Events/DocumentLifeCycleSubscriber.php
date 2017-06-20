<?php
/**
 * Copyright (c) 2017. Ambroise Maupate and Julien Blanchet
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
 * @file DocumentLifeCycleSubscriber.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace RZ\Roadiz\Core\Events;

use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Monolog\Logger;
use Pimple\Container;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Utils\Asset\Packages;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Handle file management on documents lifecycle events.
 *
 * @package Roadiz\Core\Events
 */
class DocumentLifeCycleSubscriber implements EventSubscriber
{
    /**
     * @var Container
     */
    private $container;

    /**
     * DocumentLifeCycleSubscriber constructor.
     *
     * We need to pass whole container not to trigger asset packages
     * initialization and not to creation a dependency infinite loop.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return array(
            Events::postRemove,
        );
    }

    /**
     * Unlink file after document has been deleted.
     *
     * @param LifecycleEventArgs $args
     */
    public function postRemove(LifecycleEventArgs $args)
    {
        $document = $args->getObject();
        if ($document instanceof Document) {
            $fileSystem = new Filesystem();

            $document->setRawDocument(null);
            /** @var Packages $packages */
            $packages = $this->container->offsetGet('assetPackages');
            $documentPath = $packages->getDocumentFilePath($document);

            if ($fileSystem->exists($documentPath)) {
                $fileSystem->remove($documentPath);
            }
            $this->cleanFileDirectory($document);
        }
    }

    /**
     * Remove document directory if there is no other file in it.
     *
     * @param Document $document
     * @return bool
     */
    protected function cleanFileDirectory(Document $document)
    {
        /** @var Packages $packages */
        $packages = $this->container->offsetGet('assetPackages');

        /** @var Logger $logger */
        $logger = $this->container->offsetGet('logger');

        $documentFolderPath = $packages->getDocumentFolderPath($document);
        $fileSystem = new Filesystem();

        if ($fileSystem->exists($documentFolderPath)) {
            $isDirEmpty = !(new \FilesystemIterator($documentFolderPath))->valid();
            if ($isDirEmpty) {
                $fileSystem->remove($documentFolderPath);
                $logger->debug('Document folder is empty, deleting…', ['folder' => $documentFolderPath]);
            }
        }

        return false;
    }
}
