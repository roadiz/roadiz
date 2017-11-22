<?php
/**
 * Copyright © 2017, Ambroise Maupate and Julien Blanchet
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
 * @file NonRootPackagesTest.php
 * @author Ambroise Maupate
 */

use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Tests\DefaultThemeDependentCase;
use RZ\Roadiz\Utils\Asset\Packages;
use Symfony\Component\Asset\Context\RequestStackContext;
use Symfony\Component\Asset\VersionStrategy\EmptyVersionStrategy;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class NonRootPackagesTest extends DefaultThemeDependentCase
{
    /**
     * @return Request
     */
    public function getRequest()
    {
        return new Request([], [], [], [], [], [
            'REQUEST_URI' => '/test/',
            'SCRIPT_NAME' => '/test/index.php',
            'SCRIPT_FILENAME' => '/var/www/test/index.php',
            'PHP_SELF' => '/test/index.php',
            'REQUEST_METHOD' => 'GET',
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => 80,
            'HTTP_HOST' => 'localhost',
            'REDIRECT_URL' => '/test/',
            'PATH_INFO' => '/',
            'PATH_TRANSLATED' => '/',
            'DOCUMENT_ROOT' => '/var/www/',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'HTTP_ACCEPT_LANGUAGE' => 'en-us,en;q=0.5',
            'HTTP_ACCEPT_CHARSET' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.7',
        ]);
    }

    /**
     * @dataProvider documentUrlWithBasePathProvider
     * @param Document $document
     * @param array $options
     * @param $absolute
     * @param $expectedUrl
     */
    public function testDocumentUrlWithBasePath(Document $document, array $options, $absolute, $expectedUrl)
    {
        $kernel = new Kernel('test', true);
        $kernel->boot();
        $kernel->handle($this->getRequest());

        /** @var \RZ\Roadiz\Utils\UrlGenerators\DocumentUrlGenerator $documentUrlGenerator */
        $documentUrlGenerator = $kernel->get('document.url_generator');
        $documentUrlGenerator->setDocument($document);
        $documentUrlGenerator->setOptions($options);

        // Assert
        $this->assertEquals($expectedUrl, $documentUrlGenerator->getUrl($absolute));

        $kernel->shutdown();
    }

    /**
     *
     */
    public function testBaseUrl()
    {
        $kernel = new Kernel('test', true);
        $kernel->boot();
        $kernel->handle($this->getRequest());

        $this->assertEquals(
            '/test',
            $kernel->get('request')->getBaseUrl()
        );

        $kernel->shutdown();
    }

    public function testBasePath()
    {
        $request = $this->getRequest();
        $requestStack = new RequestStack();
        $requestStack->push($request);
        $requestStackContext = new RequestStackContext($requestStack);

        $this->assertEquals('/test', $requestStackContext->getBasePath());
    }

    /**
     * @dataProvider getUrlProvider
     * @param $relativePath
     * @param $expected
     */
    public function testGetUrl($relativePath, $expected)
    {
        $kernel = new Kernel('test', true);
        $kernel->boot();

        $requestStack = new RequestStack();
        $requestStack->push($this->getRequest());
        $packages = new Packages(new EmptyVersionStrategy(), $requestStack, $kernel);

        $this->assertEquals(
            $expected,
            $packages->getUrl($relativePath, Packages::DOCUMENTS)
        );

        $this->assertEquals(
            '/test/themes/DefaultTheme/static/css/styles.css',
            $packages->getUrl('themes/DefaultTheme/static/css/styles.css')
        );

        $kernel->shutdown();
    }

    public function getUrlProvider()
    {
        return [
            [
                'some-custom-file.jpg',
                '/test/files/some-custom-file.jpg',
            ],
            [
                'folder/some-custom-file.jpg',
                '/test/files/folder/some-custom-file.jpg',
            ],
            [
                'folder/folder2/some-custom-file.jpg',
                '/test/files/folder/folder2/some-custom-file.jpg',
            ]
        ];
    }

    /**
     * Symfony Assets component broke BC on version 2.8.20
     * with path starting with a slash.
     *
     * This test is meant to check if they revert this change.
     */
    public function testGetUrlWithSlash()
    {
        $kernel = new Kernel('test', true);
        $kernel->boot();

        $requestStack = new RequestStack();
        $requestStack->push($this->getRequest());
        $packages = new Packages(new EmptyVersionStrategy(), $requestStack, $kernel);

        $this->assertNotEquals(
            '/test/files/some-custom-file',
            $packages->getUrl('/some-custom-file', Packages::DOCUMENTS)
        );

        $this->assertNotEquals(
            '/test/files/folder/some-custom-file',
            $packages->getUrl('/folder/some-custom-file', Packages::DOCUMENTS)
        );

        $kernel->shutdown();
    }

    /**
     * @return array
     */
    public function documentUrlWithBasePathProvider()
    {
        $document1 = new Document();
        $document1->setFolder('folder');
        $document1->setFilename('file.jpg');
        $document1->setMimeType('image/jpeg');

        return [
            [
                $document1,
                [
                    'quality' => 80
                ],
                false,
                '/test/assets/q80/folder/file.jpg',
            ],
            [
                $document1,
                [
                    'quality' => 90,
                    'width' => 600,
                ],
                true,
                'http://localhost/test/assets/w600-q90/folder/file.jpg',
            ],
            [
                $document1,
                [
                    'noProcess' => true,
                ],
                true,
                'http://localhost/test/files/folder/file.jpg',
            ],
            [
                $document1,
                [
                    'noProcess' => true,
                ],
                false,
                '/test/files/folder/file.jpg',
            ]
        ];
    }
}
