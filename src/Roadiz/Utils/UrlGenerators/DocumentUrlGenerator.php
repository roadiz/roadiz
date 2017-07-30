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
 * @file DocumentUrlGenerator.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace RZ\Roadiz\Utils\UrlGenerators;

use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Utils\Asset\Packages;
use RZ\Roadiz\Utils\Document\ViewOptionsResolver;
use Symfony\Component\HttpFoundation\RequestStack;

class DocumentUrlGenerator implements UrlGeneratorInterface
{
    /**
     * @var RequestStack
     */
    private $requestStack;
    /**
     * @var Document
     */
    private $document;

    /** @var array */
    private $options;
    /**
     * @var Packages
     */
    private $packages;
    /**
     * @var \Symfony\Component\Routing\Generator\UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * DocumentUrlGenerator constructor.
     * @param RequestStack $requestStack
     * @param Document $document
     * @param Packages $packages
     * @param \Symfony\Component\Routing\Generator\UrlGeneratorInterface $urlGenerator
     * @param array $options
     */
    public function __construct(
        RequestStack $requestStack,
        Packages $packages,
        \Symfony\Component\Routing\Generator\UrlGeneratorInterface $urlGenerator,
        Document $document = null,
        array $options = []
    ) {
        $this->requestStack = $requestStack;
        $this->document = $document;
        $this->packages = $packages;
        $this->urlGenerator = $urlGenerator;

        $this->setOptions($options);
    }

    public function setOptions(array $options = [])
    {
        $resolver = new ViewOptionsResolver();
        $this->options = $resolver->resolve($options);
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
     * @return DocumentUrlGenerator
     */
    public function setDocument(Document $document)
    {
        $this->document = $document;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getUrl($absolute = false)
    {
        if ($this->options['noProcess'] === true || !$this->document->isImage()) {
            $documentPackageName = $absolute ? Packages::ABSOLUTE_DOCUMENTS : Packages::DOCUMENTS;
            return $this->packages->getUrl(
                $this->document->getRelativeUrl(),
                $documentPackageName
            );
        }

        $defaultPackageName = $absolute ? Packages::ABSOLUTE : null;
        return $this->packages->getUrl(
            $this->getProcessedDocumentUrlByArray(),
            $defaultPackageName
        );
    }

    /**
     * @return string
     */
    protected function getProcessedDocumentUrlByArray()
    {
        $interventionRequestOptions = [];

        if (null === $this->options['fit'] && $this->options['width'] > 0) {
            $interventionRequestOptions['w'] = 'w' . (int) $this->options['width'];
        }
        if (null === $this->options['fit'] && $this->options['height'] > 0) {
            $interventionRequestOptions['h'] = 'h' . (int) $this->options['height'];
        }
        if (null !== $this->options['crop']) {
            $interventionRequestOptions['c'] = 'c' . strip_tags($this->options['crop']);
        }
        if ($this->options['blur'] > 0) {
            $interventionRequestOptions['l'] = 'l' . ($this->options['blur']);
        }
        if (null !== $this->options['fit']) {
            $interventionRequestOptions['f'] = 'f' . strip_tags($this->options['fit']);
        }
        if ($this->options['rotate'] > 0) {
            $interventionRequestOptions['r'] = 'r' . ($this->options['rotate']);
        }
        if ($this->options['sharpen'] > 0) {
            $interventionRequestOptions['s'] = 's' . ($this->options['sharpen']);
        }
        if ($this->options['contrast'] > 0) {
            $interventionRequestOptions['k'] = 'k' . ($this->options['contrast']);
        }
        if ($this->options['grayscale']) {
            $interventionRequestOptions['g'] = 'g1';
        }
        if ($this->options['quality'] > 0) {
            $interventionRequestOptions['q'] = 'q' . $this->options['quality'];
        }
        if (null !== $this->options['background']) {
            $interventionRequestOptions['b'] = 'b' . strip_tags($this->options['background']);
        }
        if ($this->options['progressive']) {
            $interventionRequestOptions['p'] = 'p1';
        }

        $routeParams = [
            'queryString' => implode('-', $interventionRequestOptions),
            'filename' => $this->document->getRelativeUrl(),
        ];

        $path = $this->urlGenerator->generate(
            'interventionRequestProcess',
            $routeParams,
            \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_PATH
        );

        /*
         * Need to remove base-path from url as AssetPackages will prepend it.
         */
        $path = $this->removeBasePath($path);

        return $this->removeStartingSlash($path);
    }

    /**
     * Need to remove base-path from url as AssetPackages will prepend it.
     *
     * @param string $path
     * @return bool|string
     */
    protected function removeBasePath($path)
    {
        $basePath = $this->requestStack->getMasterRequest()->getBasePath();
        if ($basePath != '') {
            $path = substr($path, strlen($basePath));
        }

        return $path;
    }

    /**
     * Remove root-slash not to disable Assets Packages resolving
     * real server root.
     *
     * @param string $path
     * @return string
     */
    protected function removeStartingSlash($path)
    {
        if (substr($path, 0, 1) === '/') {
            $path = substr($path, 1);
        }

        return $path;
    }
}
