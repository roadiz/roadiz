<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Controllers;

use AM\InterventionRequest\ShortUrlExpander;
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
     * @param string  $queryString
     * @param string  $filename
     *
     * @return Response
     */
    public function interventionRequestAction(Request $request, $queryString, $filename)
    {
        try {
            /*
             * Handle short url with Url rewriting
             */
            $expander = new ShortUrlExpander($request);
            $expander->setIgnorePath($this->get('kernel')->getPublicCacheBasePath());
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
     * @param string  $filename
     * @param int     $variant
     * @param string  $extension
     *
     * @return Response
     * @throws \Exception
     */
    public function fontFileAction(Request $request, string $filename, int $variant, string $extension)
    {
        /** @var FontRepository $repository */
        $repository = $this->get('em')->getRepository(Font::class);
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
                    $fontpath = null;
                    $mime = "application/octet-stream";
                    break;
            }

            if (null !== $fontpath &&
                file_exists($fontpath) &&
                is_file($fontpath)) {
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
     * @throws \Exception
     */
    public function fontFacesAction(Request $request)
    {
        /** @var FontRepository $repository */
        $repository = $this->get('em')->getRepository(Font::class);
        $lastMod = $repository->getLatestUpdateDate();

        $response = new Response(
            '',
            Response::HTTP_NOT_MODIFIED,
            ['content-type' => 'text/css']
        );
        $cacheConfig = [
            'max_age' => 60 * 60 * 48, // expires for 2 days
            'public' => true,
        ];
        if (null !== $lastMod) {
            $cacheConfig['last_modified'] = new \DateTime($lastMod);
        }
        $response->setCache($cacheConfig);

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
