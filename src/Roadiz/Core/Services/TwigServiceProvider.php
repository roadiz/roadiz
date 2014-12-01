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
 * @file TwigServiceProvider.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Services;

use Pimple\Container;

use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Extension\RoutingExtension;
use Symfony\Bridge\Twig\Form\TwigRenderer;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;

use \Michelf\Markdown;
use RZ\Roadiz\Core\Utils\InlineMarkdown;

/**
 * Register Twig services for dependency injection container.
 */
class TwigServiceProvider implements \Pimple\ServiceProviderInterface
{
    /**
     * @param Pimple\Container $container [description]
     */
    public function register(Container $container)
    {
        $container['twig.cacheFolder'] = function ($c) {
            return RENZO_ROOT.'/cache/twig_cache';
        };

        /*
         * Return every paths to search for twig templates.
         */
        $container['twig.loaderFileSystem'] = function ($c) {
            $vendorDir = realpath(RENZO_ROOT . '/vendor');

            // le chemin vers TwigBridge pour que Twig puisse localiser
            // le fichier form_div_layout.html.twig
            $vendorTwigBridgeDir =
                $vendorDir . '/symfony/twig-bridge/Symfony/Bridge/Twig';

            return new \Twig_Loader_Filesystem(array(
                // Default Form extension templates
                $vendorTwigBridgeDir.'/Resources/views/Form',
                RENZO_ROOT.'/src/Roadiz/CMS/Resources/views',
            ));
        };

        /*
         * Main twig environment
         */
        $container['twig.environment'] = function ($c) {

            $devMode = (isset($c['config']['devMode']) && true === (boolean) $c['config']['devMode']) ?
                        true :
                        false;

            $twig = new \Twig_Environment($c['twig.loaderFileSystem'], array(
                'debug' => $devMode,
                'cache' => $c['twig.cacheFolder'],
            ));

            $c['twig.formRenderer']->setEnvironment($twig);

            $twig->addExtension(
                new FormExtension(new TwigRenderer(
                    $c['twig.formRenderer'],
                    $c['csrfProvider']
                ))
            );

            $twig->addFilter($c['twig.markdownExtension']);
            $twig->addFilter($c['twig.centralTruncateExtension']);

            /*
             * Extensions
             */
            $twig->addExtension(new \Twig_Extensions_Extension_Intl());
            $twig->addExtension($c['twig.routingExtension']);
            $twig->addExtension(new \Twig_Extensions_Extension_Text());

            if ($devMode) {
                $twig->addExtension(new \Twig_Extension_Debug());
            }

            return $twig;
        };

        /*
         * Twig form renderer extension
         */
        $container['twig.formRenderer'] = function ($c) {

            return new TwigRendererEngine(array(
                'form_div_layout.html.twig'
            ));
        };

        /*
         * Twig routing extension
         */
        $container['twig.routingExtension'] = function ($c) {

            return new RoutingExtension($c['urlGenerator']);
        };

        /*
         * Markdown extension
         */
        $container['twig.markdownExtension'] = function ($c) {

            return new \Twig_SimpleFilter('markdown', function ($object) {
                return Markdown::defaultTransform($object);
            }, array('is_safe' => array('html')));
        };

        /*
         * InlineMarkdown extension
         */
        $container['twig.inlineMarkdownExtension'] = function ($c) {

            return new \Twig_SimpleFilter('inlineMarkdown', function ($object) {
                return InlineMarkdown::defaultTransform($object);
            }, array('is_safe' => array('html')));
        };

        /*
         * Central Truncate extension
         */
        $container['twig.centralTruncateExtension'] = function ($c) {

            return new \Twig_SimpleFilter(
                'centralTruncate',
                function ($object, $length, $offset = 0, $ellipsis = "[…]") {
                    if (strlen($object) > $length + strlen($ellipsis)) {
                        $str1 = substr($object, 0, floor($length/2)+ floor($offset/2));
                        $str2 = substr($object, (floor($length/2)* -1)+ floor($offset/2));

                        return $str1 . $ellipsis . $str2;
                    } else {
                        return $object;
                    }
                }
            );
        };

        return $container;
    }
}
