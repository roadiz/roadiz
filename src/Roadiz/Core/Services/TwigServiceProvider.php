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
 * @file TwigServiceProvider.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Services;

use Asm89\Twig\CacheExtension\CacheProvider\DoctrineCacheAdapter;
use Asm89\Twig\CacheExtension\CacheStrategy\LifetimeCacheStrategy;
use Asm89\Twig\CacheExtension\Extension as CacheExtension;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\Roadiz\Core\Bags\SettingsBag;
use RZ\Roadiz\Utils\TwigExtensions\BlockRenderExtension;
use RZ\Roadiz\Utils\TwigExtensions\DocumentExtension;
use RZ\Roadiz\Utils\TwigExtensions\NodesSourcesExtension;
use RZ\Roadiz\Utils\TwigExtensions\TranslationExtension as RoadizTranslationExtension;
use RZ\Roadiz\Utils\TwigExtensions\UrlExtension;
use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Extension\HttpFoundationExtension;
use Symfony\Bridge\Twig\Extension\RoutingExtension;
use Symfony\Bridge\Twig\Extension\SecurityExtension;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Bridge\Twig\Form\TwigRenderer;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use \Parsedown;

/**
 * Register Twig services for dependency injection container.
 */
class TwigServiceProvider implements ServiceProviderInterface
{
    /**
     * @param \Pimple\Container $container [description]
     * @return Container
     */
    public function register(Container $container)
    {
        $container['twig.cacheFolder'] = function ($c) {
            return $c['kernel']->getCacheDir() . '/twig_cache';
        };

        /*
         * Return every paths to search for twig templates.
         */
        $container['twig.loaderFileSystem'] = function () {
            $vendorDir = realpath(ROADIZ_ROOT . '/vendor');

            // le chemin vers TwigBridge pour que Twig puisse localiser
            // le fichier form_div_layout.html.twig
            $vendorTwigBridgeDir =
            $vendorDir . '/symfony/twig-bridge';

            return new \Twig_Loader_Filesystem([
                // Default Form extension templates
                $vendorTwigBridgeDir . '/Resources/views/Form',
                ROADIZ_ROOT . '/src/Roadiz/CMS/Resources/views',
            ]);
        };

        /*
         * Main twig environment
         */
        $container['twig.environment'] = function ($c) {
            $c['stopwatch']->start('initTwig');
            $twig = new \Twig_Environment($c['twig.loaderFileSystem'], [
                'debug' => $c['kernel']->isDebug(),
                'cache' => $c['twig.cacheFolder'],
            ]);

            $c['twig.formRenderer']->setEnvironment($twig);

            $twig->addExtension(
                new FormExtension(new TwigRenderer(
                    $c['twig.formRenderer'],
                    $c['csrfTokenManager']
                ))
            );

            $twig->addFilter($c['twig.markdownExtension']);
            $twig->addFilter($c['twig.inlineMarkdownExtension']);
            $twig->addFilter($c['twig.centralTruncateExtension']);

            /*
             * Extensions
             */
            $twig->addExtension(new HttpFoundationExtension($c['requestStack']));
            $twig->addExtension(new SecurityExtension($c['securityAuthorizationChecker']));
            $twig->addExtension(new TranslationExtension($c['translator']));
            $twig->addExtension(new \Twig_Extensions_Extension_Intl());
            $twig->addExtension($c['twig.routingExtension']);
            $twig->addExtension(new \Twig_Extensions_Extension_Text());
            $twig->addExtension(new BlockRenderExtension($c));
            if (true !== $c['kernel']->isInstallMode()) {
                $twig->addExtension(new NodesSourcesExtension(
                    $c['securityAuthorizationChecker'],
                    $c['kernel']->isPreview()
                ));
            }
            $twig->addExtension(new DocumentExtension());
            $twig->addExtension(new UrlExtension(
                $c['request'],
                $c['nodesSourcesUrlCacheProvider'],
                (boolean) SettingsBag::get('force_locale')
            ));
            $twig->addExtension(new RoadizTranslationExtension($c['request']));

            if (null !== $c['twig.cacheExtension']) {
                $twig->addExtension($c['twig.cacheExtension']);
            }

            if (true === $c['kernel']->isDebug()) {
                $twig->addExtension(new \Twig_Extension_Debug());
            }
            $c['stopwatch']->stop('initTwig');

            return $twig;
        };

        /*
         * Twig form renderer extension
         */
        $container['twig.formRenderer'] = function () {

            return new TwigRendererEngine([
                'form_div_layout.html.twig',
            ]);
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
        $container['twig.markdownExtension'] = function () {

            return new \Twig_SimpleFilter('markdown', function ($object) {
                return Parsedown::instance()->text($object);
            }, ['is_safe' => ['html']]);
        };

        /*
         * InlineMarkdown extension
         */
        $container['twig.inlineMarkdownExtension'] = function () {

            return new \Twig_SimpleFilter('inlineMarkdown', function ($object) {
                return Parsedown::instance()->line($object);
            }, ['is_safe' => ['html']]);
        };

        /*
         * Central Truncate extension
         */
        $container['twig.centralTruncateExtension'] = function () {

            return new \Twig_SimpleFilter(
                'centralTruncate',
                function ($object, $length, $offset = 0, $ellipsis = "[…]") {
                    if (strlen($object) > $length + strlen($ellipsis)) {
                        $str1 = substr($object, 0, floor($length / 2) + floor($offset / 2));
                        $str2 = substr($object, (floor($length / 2) * -1) + floor($offset / 2));

                        return $str1 . $ellipsis . $str2;
                    } else {
                        return $object;
                    }
                }
            );
        };
        /*
         * Twig cache extension
         * see https://github.com/asm89/twig-cache-extension
         */
        $container['twig.cacheExtension'] = function ($c) {

            $resultCacheDriver = $c['em']->getConfiguration()->getResultCacheImpl();
            if ($resultCacheDriver !== null) {
                $cacheProvider = new DoctrineCacheAdapter($resultCacheDriver);
                $cacheStrategy = new LifetimeCacheStrategy($cacheProvider);
                $cacheExtension = new CacheExtension($cacheStrategy);

                return $cacheExtension;
            } else {
                return null;
            }
        };

        return $container;
    }
}
