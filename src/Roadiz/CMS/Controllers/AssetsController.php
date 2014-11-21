<?php
/**
 * Copyright © 2014, REZO ZERO
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
 * Except as contained in this notice, the name of the REZO ZERO shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from the REZO ZERO SARL.
 *
 * @file AssetsController.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\CMS\Controllers;

use RZ\Roadiz\Core\Kernel;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Security\Core\SecurityContext;

use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;

/**
 * Special controller app file for assets managment with SLIR.
 */
class AssetsController extends AppController
{
   /**
     * Initialize controller with NO twig environment.
     */
    public function __init() { }

    /**
     * {@inheritdoc}
     */
    public function prepareBaseAssignation() { }

    /**
     * {@inheritdoc}
     */
    public static function getRoutes()
    {
        $locator = new FileLocator(array(
            RENZO_ROOT.'/src/Roadiz/CMS/Resources'
        ));

        if (file_exists(RENZO_ROOT.'/src/Roadiz/CMS/Resources/assetsRoutes.yml')) {
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

        Kernel::getService('em')->close();

        $slir = new \SLIR\SLIR();
        $slir->processRequestFromURL();

        // SLIR handle response by itself
    }

    /**
     * Request a single protected font file from RZCMS.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param string                                   $filename
     * @param string                                   $extension
     * @param string                                   $token
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function fontFileAction(Request $request, $filename, $extension, $token)
    {
        $font = Kernel::getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\Font')
            ->findOneBy(array('hash'=>$filename));

        if (null !== $font &&
            $this->getService('csrfProvider')->isCsrfTokenValid($font->getHash().$extension, $token)) {

            switch ($extension) {
                case 'eot':
                    $fontpath = $font->getEOTAbsolutePath();
                    $mime = \RZ\Roadiz\Core\Entities\Font::$extensionToMime['eot'];
                    break;
                case 'woff':
                    $fontpath = $font->getWOFFAbsolutePath();
                    $mime = \RZ\Roadiz\Core\Entities\Font::$extensionToMime['woff'];
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
                    break;
            }

            if ("" != $fontpath) {
                return new Response(
                    file_get_contents($fontpath),
                    Response::HTTP_OK,
                    array('content-type' => $mime)
                );
            }
        }

        return new Response(
            "Font Fail",
            Response::HTTP_NOT_FOUND,
            array('content-type' => 'text/html')
        );
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
        if ($this->getService('csrfProvider')
                 ->isCsrfTokenValid(static::FONT_TOKEN_INTENTION, $token)) {

            $fonts = Kernel::getService('em')
                ->getRepository('RZ\Roadiz\Core\Entities\Font')
                ->findAll();
            $fontOutput = array();

            foreach ($fonts as $font) {
                $fontOutput[] = $font->getViewer()->getCSSFontFace($this->getService('csrfProvider'));
            }

            return new Response(
                implode(PHP_EOL, $fontOutput),
                Response::HTTP_OK,
                array('content-type' => 'text/css')
            );
        } else {
            return new Response(
                "Font Fail",
                Response::HTTP_NOT_FOUND,
                array('content-type' => 'text/html')
            );
        }
    }
}
