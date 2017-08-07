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
 * @file AssetsController.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\CMS\Controllers;

use AM\InterventionRequest\InterventionRequest;
use AM\InterventionRequest\ShortUrlExpander;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use RZ\Roadiz\Core\Entities\Font;
use RZ\Roadiz\Core\Repositories\FontRepository;
use RZ\Roadiz\Utils\Asset\Packages;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Special controller app file for assets management with InterventionRequest lib.
 */
class AssetsController extends CmsController
{
    /**
     * Initialize controller with NO twig environment.
     */
    public function __init()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function prepareBaseAssignation()
    {
    }

    /**
     * @param Request $request
     * @param string $queryString
     * @param string $filename
     * @return Response
     */
    public function interventionRequestAction(Request $request, $queryString, $filename)
    {
        try {
            /*
             * Handle short url with Url rewriting
             */
            $expander = new ShortUrlExpander($request);
            $expander->injectParamsToRequest($queryString, $filename);

            /*
             * Handle main image request
             */
            $interventionRequest = $this->get('interventionRequest');
            $interventionRequest->handleRequest($request);
            return $interventionRequest->getResponse($request);
        } catch (\ReflectionException $e) {
            $message = '[Configuration] ' . $e->getMessage();
            return new Response(
                $message,
                Response::HTTP_INTERNAL_SERVER_ERROR,
                ['content-type' => 'text/plain']
            );
        } catch (\Exception $e) {
            return new Response(
                $e->getMessage(),
                Response::HTTP_NOT_FOUND,
                ['content-type' => 'text/plain']
            );
        }
    }

    /**
     * Request a single protected font file from Roadiz.
     *
     * @param Request $request
     * @param string $filename
     * @param $variant
     * @param string $extension
     *
     * @return Response
     */
    public function fontFileAction(Request $request, $filename, $variant, $extension)
    {
        /** @var FontRepository $repository */
        $repository = $this->get('em')
                           ->getRepository('RZ\Roadiz\Core\Entities\Font');
        $lastMod = $repository->getLatestUpdateDate();
        /** @var Font $font */
        $font = $repository->findOneBy(['hash' => $filename, 'variant' => $variant]);

        /** @var Packages $packages */
        $packages = $this->get('assetPackages');

        if (null !== $font) {
            switch ($extension) {
                case 'eot':
                    $fontpath = $packages->getFontsPath($font->getEOTRelativeUrl());
                    $mime = Font::MIME_EOT;
                    break;
                case 'woff':
                    $fontpath = $packages->getFontsPath($font->getWOFFRelativeUrl());
                    $mime = Font::MIME_WOFF;
                    break;
                case 'woff2':
                    $fontpath = $packages->getFontsPath($font->getWOFF2RelativeUrl());
                    $mime = Font::MIME_WOFF2;
                    break;
                case 'svg':
                    $fontpath = $packages->getFontsPath($font->getSVGRelativeUrl());
                    $mime = Font::MIME_SVG;
                    break;
                case 'otf':
                    $mime = Font::MIME_OTF;
                    $fontpath = $packages->getFontsPath($font->getOTFRelativeUrl());
                    break;
                case 'ttf':
                    $mime = Font::MIME_TTF;
                    $fontpath = $packages->getFontsPath($font->getOTFRelativeUrl());
                    break;
                default:
                    $fontpath = "";
                    $mime = "application/octet-stream";
                    break;
            }

            if ("" != $fontpath && file_exists($fontpath)) {
                $response = new Response(
                    '',
                    Response::HTTP_NOT_MODIFIED,
                    [
                        'content-type' => $mime,
                    ]
                );
                $response->setCache([
                    'last_modified' => new \DateTime($lastMod),
                    'max_age' => 60 * 60 * 48, // expires for 2 days
                    'public' => true,
                ]);
                if (!$response->isNotModified($request)) {
                    $response->setContent(file_get_contents($fontpath));
                    $response->setStatusCode(Response::HTTP_OK);
                    $response->setEtag(md5($response->getContent()));
                }

                return $response;
            }
        }
        $msg = "Font doesn't exist " . $filename;
        return new Response(
            $msg,
            Response::HTTP_NOT_FOUND,
            ['content-type' => 'text/html']
        );
    }

    /**
     * Request the font-face CSS file listing available fonts.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function fontFacesAction(Request $request)
    {
        /** @var FontRepository $repository */
        $repository = $this->get('em')
            ->getRepository('RZ\Roadiz\Core\Entities\Font');
        $lastMod = $repository->getLatestUpdateDate();

        $response = new Response(
            '',
            Response::HTTP_NOT_MODIFIED,
            ['content-type' => 'text/css']
        );
        $response->setCache([
            'last_modified' => new \DateTime($lastMod),
            'max_age' => 60 * 60 * 48, // expires for 2 days
            'public' => true,
        ]);

        if ($response->isNotModified($request)) {
            return $response;
        }

        $fonts = $repository->findAll();

        $assignation = [
            'fonts' => [],
        ];
        /** @var Font $font */
        foreach ($fonts as $font) {
            $variantHash = $font->getHash() . $font->getVariant();
            $assignation['fonts'][] = [
                'font' => $font,
                'site' => $this->get('settingsBag')->get('site_name'),
                'fontFolder' => $this->get('kernel')->getFontsFilesBasePath(),
                'variantHash' => $variantHash,
            ];
        }
        $response->setContent(
            $this->get('twig.environment')->render(
                'fonts/fontfamily.css.twig',
                $assignation
            )
        );
        $response->setEtag(md5($response->getContent()));
        $response->setStatusCode(Response::HTTP_OK);

        return $response;
    }
}
