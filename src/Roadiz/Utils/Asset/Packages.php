<?php
/**
 * Copyright © 2015, Ambroise Maupate and Julien Blanchet
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
 * @file Packages.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Utils\Asset;

use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\FileAwareInterface;
use Symfony\Component\Asset\Context\RequestStackContext;
use Symfony\Component\Asset\Packages as BasePackages;
use Symfony\Component\Asset\PathPackage;
use Symfony\Component\Asset\UrlPackage;
use Symfony\Component\Asset\VersionStrategy\VersionStrategyInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class Packages
 * @package RZ\Roadiz\Utils\Asset
 */
class Packages extends BasePackages
{
    /**
     * Absolute package is for reaching
     * resources at server root.
     */
    const ABSOLUTE = 'absolute';

    /**
     * Document package is for reaching
     * files with relative path to server root.
     */
    const DOCUMENTS = 'doc';

    /**
     * Document package is for reaching
     * files with absolute url with domain-name.
     */
    const ABSOLUTE_DOCUMENTS = 'absolute_doc';

    /**
     * Public path package is for internally reaching
     * public files with absolute path.
     * Be careful, this provides server paths.
     */
    const PUBLIC_PATH = 'public_path';

    /**
     * Private path package is for internally reaching
     * private files with absolute path.
     * Be careful, this provides server paths.
     */
    const PRIVATE_PATH = 'private_path';

    /**
     * Fonts path package is for internally reaching
     * font files with absolute path.
     * Be careful, this provides server paths.
     */
    const FONTS_PATH = 'fonts_path';

    /**
     * @var FileAwareInterface
     */
    private $fileAware;
    /**
     * @var string
     */
    private $staticDomain;
    /**
     * @var bool
     */
    private $isPreview;
    /**
     * @var VersionStrategyInterface
     */
    private $versionStrategy;
    /**
     * @var RequestStackContext
     */
    private $requestStackContext;
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * Build a new asset packages for Roadiz root and documents.
     *
     * @param VersionStrategyInterface $versionStrategy
     * @param RequestStack $requestStack
     * @param FileAwareInterface $fileAware
     * @param string $staticDomain
     * @param bool $isPreview
     */
    public function __construct(
        VersionStrategyInterface $versionStrategy,
        RequestStack $requestStack,
        FileAwareInterface $fileAware,
        $staticDomain = "",
        $isPreview = false
    ) {
        $this->requestStackContext = new RequestStackContext($requestStack);
        $this->requestStack = $requestStack;
        $this->fileAware = $fileAware;
        $this->staticDomain = $staticDomain;
        $this->isPreview = $isPreview;
        $this->versionStrategy = $versionStrategy;

        parent::__construct($this->getDefaultPackage(), [
            static::ABSOLUTE => $this->getAbsoluteDefaultPackage(),
            static::DOCUMENTS => $this->getDocumentPackage(),
            static::ABSOLUTE_DOCUMENTS => $this->getAbsoluteDocumentPackage(),
            static::PUBLIC_PATH => $this->getPublicPathPackage(),
            static::PRIVATE_PATH => $this->getPrivatePathPackage(),
            static::FONTS_PATH => $this->getFontsPathPackage(),
        ]);
    }

    /**
     * @return bool
     */
    public function useStaticDomain()
    {
        return (false === $this->isPreview && $this->staticDomain != "");
    }

    /**
     * @return string
     */
    protected function getStaticDomainAndPort()
    {
        /*
         * Add non-default port to static domain.
         */
        $staticDomainAndPort = $this->staticDomain;
        if (($this->requestStackContext->isSecure() && $this->getRequest()->getPort() != 443) ||
            (!$this->requestStackContext->isSecure() && $this->getRequest()->getPort() != 80)) {
            $staticDomainAndPort .= ':' . $this->getRequest()->getPort();
        }

        /*
         * If no protocol, use https as default
         */
        if (!preg_match("~^(?:f|ht)tps?://~i", $staticDomainAndPort)) {
            $staticDomainAndPort = "https://" . $staticDomainAndPort;
        }

        return $staticDomainAndPort;
    }

    /**
     * @return PathPackage|UrlPackage
     */
    protected function getDefaultPackage()
    {
        if ($this->useStaticDomain()) {
            return new UrlPackage(
                $this->getStaticDomainAndPort(),
                $this->versionStrategy
            );
        }

        return new PathPackage(
            '/',
            $this->versionStrategy,
            $this->requestStackContext
        );
    }

    /**
     * @return PathPackage|UrlPackage
     */
    protected function getAbsoluteDefaultPackage()
    {
        if ($this->useStaticDomain()) {
            return $this->getDefaultPackage();
        }

        return new UrlPackage(
            $this->getRequest()->getSchemeAndHttpHost() . $this->requestStackContext->getBasePath(),
            $this->versionStrategy
        );
    }

    /**
     * @return PathPackage|UrlPackage
     */
    protected function getDocumentPackage()
    {
        if ($this->useStaticDomain()) {
            return new UrlPackage(
                $this->getStaticDomainAndPort() . $this->fileAware->getPublicFilesBasePath(),
                $this->versionStrategy
            );
        }

        return new PathPackage(
            $this->fileAware->getPublicFilesBasePath(),
            $this->versionStrategy,
            $this->requestStackContext
        );
    }

    /**
     * @return PathPackage|UrlPackage
     */
    protected function getAbsoluteDocumentPackage()
    {
        if ($this->useStaticDomain()) {
            return $this->getDocumentPackage();
        }

        return new UrlPackage(
            $this->getRequest()->getSchemeAndHttpHost() . $this->requestStackContext->getBasePath() . $this->fileAware->getPublicFilesBasePath(),
            $this->versionStrategy
        );
    }

    /**
     * @return PathPackage
     */
    protected function getPublicPathPackage()
    {
        return new PathPackage(
            $this->fileAware->getPublicFilesPath(),
            $this->versionStrategy
        );
    }

    /**
     * @return PathPackage
     */
    protected function getPrivatePathPackage()
    {
        return new PathPackage(
            $this->fileAware->getPrivateFilesPath(),
            $this->versionStrategy
        );
    }

    /**
     * @return PathPackage
     */
    protected function getFontsPathPackage()
    {
        return new PathPackage(
            $this->fileAware->getFontsFilesPath(),
            $this->versionStrategy
        );
    }

    /**
     * Shortcut for $this->getUrl($relativePath, static::FONTS_PATH).
     *
     * @param $relativePath
     * @return string
     */
    public function getFontsPath($relativePath)
    {
        return $this->getUrl($relativePath, static::FONTS_PATH);
    }

    /**
     * Shortcut for $this->getUrl($relativePath, static::PUBLIC_PATH).
     *
     * @param $relativePath
     * @return string
     */
    public function getPublicFilesPath($relativePath)
    {
        return $this->getUrl($relativePath, static::PUBLIC_PATH);
    }

    /**
     * Shortcut for $this->getUrl($relativePath, static::PRIVATE_PATH).
     *
     * @param $relativePath
     * @return string
     */
    public function getPrivateFilesPath($relativePath)
    {
        return $this->getUrl($relativePath, static::PRIVATE_PATH);
    }

    /**
     * @param Document $document
     * @return string Document file absolute path according if document is private or not.
     */
    public function getDocumentFilePath(Document $document)
    {
        if ($document->isPrivate()) {
            return $this->getPrivateFilesPath($document->getRelativeUrl());
        }
        return $this->getPublicFilesPath($document->getRelativeUrl());
    }

    /**
     * @param Document $document
     * @return string Document folder absolute path according if document is private or not.
     */
    public function getDocumentFolderPath(Document $document)
    {
        if ($document->isPrivate()) {
            return $this->getPrivateFilesPath($document->getFolder());
        }
        return $this->getPublicFilesPath($document->getFolder());
    }

    /**
     * @return string
     */
    public function getStaticDomain()
    {
        return $this->staticDomain;
    }

    /**
     * @param string $staticDomain
     * @return Packages
     */
    public function setStaticDomain($staticDomain)
    {
        $this->staticDomain = $staticDomain;
        return $this;
    }

    /**
     * @return null|Request
     */
    protected function getRequest()
    {
        return $this->requestStack->getMasterRequest();
    }
}
