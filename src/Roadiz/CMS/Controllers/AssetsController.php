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

use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Loader\YamlFileLoader;

/**
 * Special controller app file for assets managment with SLIR.
 */
class AssetsController extends AppController
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
     * {@inheritdoc}
     */
    public static function getRoutes()
    {
        $locator = new FileLocator([
            ROADIZ_ROOT . '/src/Roadiz/CMS/Resources',
        ]);

        if (file_exists(ROADIZ_ROOT . '/src/Roadiz/CMS/Resources/assetsRoutes.yml')) {
            $loader = new YamlFileLoader($locator);

            return $loader->load('assetsRoutes.yml');
        }

        return null;
    }

    /**
     * Handle images resize with SLIR vendor.
     *
     * @param string $queryString
     * @param string $filename
     */
    public function slirAction($queryString, $filename)
    {
        define('SLIR_CONFIG_CLASSNAME', '\RZ\Roadiz\CMS\Utils\SLIRConfig');

        $slir = new \SLIR\SLIR();
        $slir->processRequestFromURL();

        // SLIR handle response by itself
    }

    /**
     * Request a single protected font file from Roadiz.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param string                                   $filename
     * @param string                                   $extension
     * @param string                                   $token
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function fontFileAction(Request $request, $filename, $variant, $extension, $token)
    {
        $font = $this->getService('em')
                     ->getRepository('RZ\Roadiz\Core\Entities\Font')
                     ->findOneBy(['hash' => $filename, 'variant' => $variant]);

        if (null !== $font) {
            if ($this->getService('csrfProvider')->isCsrfTokenValid($font->getHash() . $font->getVariant(), $token)) {
                switch ($extension) {
                    case 'eot':
                        $fontpath = $font->getEOTAbsolutePath();
                        $mime = \RZ\Roadiz\Core\Entities\Font::$extensionToMime['eot'];
                        break;
                    case 'woff':
                        $fontpath = $font->getWOFFAbsolutePath();
                        $mime = \RZ\Roadiz\Core\Entities\Font::$extensionToMime['woff'];
                        break;
                    case 'woff2':
                        $fontpath = $font->getWOFF2AbsolutePath();
                        $mime = \RZ\Roadiz\Core\Entities\Font::$extensionToMime['woff2'];
                        break;
                    case 'svg':
                        $fontpath = $font->getSVGAbsolutePath();
                        $mime = \RZ\Roadiz\Core\Entities\Font::$extensionToMime['svg'];
                        break;
                    case 'otf':
                    case 'ttf':
                        $fontpath = $font->getOTFAbsolutePath();
                        $mime = \RZ\Roadiz\Core\Entities\Font::$extensionToMime['otf'];
                        break;
                    default:
                        $fontpath = "";
                        $mime = "text/html";
                        break;
                }

                if ("" != $fontpath) {
                    $response = new Response(
                        file_get_contents($fontpath),
                        Response::HTTP_OK,
                        ['content-type' => $mime]
                    );
                    $date = new \DateTime();
                    $date->modify('+2 hours');
                    $response->setExpires($date);
                    $response->setPrivate(true);
                    $response->setMaxAge(60*60*2);

                    return $response;
                }
            } else {
                return new Response(
                    "Font Fail " . $token,
                    Response::HTTP_NOT_FOUND,
                    ['content-type' => 'text/html']
                );
            }

        } else {
            return new Response(
                "Font doesn't exist " . $filename,
                Response::HTTP_NOT_FOUND,
                ['content-type' => 'text/html']
            );
        }
    }

    /**
     * Request the font-face CSS file listing available fonts.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param string                                   $token
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function fontFacesAction(Request $request, $token)
    {
        $repository = $this->getService('em')->getRepository('RZ\Roadiz\Core\Entities\Font');
        $lastMod = $repository->getLatestUpdateDate();

        $response = new Response(
            '',
            Response::HTTP_NOT_MODIFIED,
            ['content-type' => 'text/css']
        );
        $response->setCache([
            'last_modified' => new \DateTime($lastMod),
            'max_age' => 60*60*2,
            'public' => false,
        ]);

        if ($response->isNotModified($request)) {
            return $response;
        }

        $fonts = $repository->findAll();

        $fontOutput = [];

        foreach ($fonts as $font) {
            $fontOutput[] = $font->getViewer()->getCSSFontFace($this->getService('csrfProvider'));
        }

        $response->setContent(implode(PHP_EOL, $fontOutput));
        $response->setStatusCode(Response::HTTP_OK);

        return $response;
    }
}
