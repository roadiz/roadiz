<?php
/**
 * Copyright REZO ZERO 2014
 *
 *
 * @file BackendController.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\CMS\Controllers;

use RZ\Renzo\Core\Kernel;

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
     *
     * @param \Symfony\Component\Security\Core\SecurityContext $securityContext
     */
    public function __init(SecurityContext $securityContext)
    {

    }

    /**
     * {@inheritdoc}
     */
    public static function getRoutes()
    {
        $locator = new FileLocator(array(
            RENZO_ROOT.'/src/Renzo/CMS/Resources'
        ));

        if (file_exists(RENZO_ROOT.'/src/Renzo/CMS/Resources/assetsRoutes.yml')) {
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
        define('SLIR_CONFIG_CLASSNAME', '\RZ\Renzo\CMS\Utils\SLIRConfig');

        Kernel::getInstance()->em()->close();

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
        $font = Kernel::getInstance()->em()
            ->getRepository('RZ\Renzo\Core\Entities\Font')
            ->findOneBy(array('hash'=>$filename));

        if (null !== $font &&
            static::$csrfProvider->isCsrfTokenValid($font->getHash().$extension, $token)) {

            switch ($extension) {
                case 'eot':
                    $fontpath = $font->getEOTAbsolutePath();
                    $mime = \RZ\Renzo\Core\Entities\Font::$extensionToMime['eot'];
                    break;
                case 'woff':
                    $fontpath = $font->getWOFFAbsolutePath();
                    $mime = \RZ\Renzo\Core\Entities\Font::$extensionToMime['woff'];
                    break;
                case 'svg':
                    $fontpath = $font->getSVGAbsolutePath();
                    $mime = \RZ\Renzo\Core\Entities\Font::$extensionToMime['svg'];
                    break;
                case 'otf':
                case 'ttf':
                    $fontpath = $font->getOTFAbsolutePath();
                    $mime = \RZ\Renzo\Core\Entities\Font::$extensionToMime['otf'];
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
        if (static::$csrfProvider->isCsrfTokenValid(static::FONT_TOKEN_INTENTION, $token)) {

            $fonts = Kernel::getInstance()->em()
                ->getRepository('RZ\Renzo\Core\Entities\Font')
                ->findAll();
            $fontOutput = array();

            foreach ($fonts as $font) {
                $fontOutput[] = $font->getViewer()->getCSSFontFace(static::$csrfProvider);
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