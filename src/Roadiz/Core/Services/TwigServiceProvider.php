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
use Doctrine\Common\Collections\ArrayCollection;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\Roadiz\CMS\Controllers\CmsController;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Utils\TwigExtensions\BlockRenderExtension;
use RZ\Roadiz\Utils\TwigExtensions\DocumentExtension;
use RZ\Roadiz\Utils\TwigExtensions\FontExtension;
use RZ\Roadiz\Utils\TwigExtensions\NodesSourcesExtension;
use RZ\Roadiz\Utils\TwigExtensions\ParsedownExtension;
use RZ\Roadiz\Utils\TwigExtensions\TranslationExtension as RoadizTranslationExtension;
use RZ\Roadiz\Utils\TwigExtensions\UrlExtension;
use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Extension\HttpFoundationExtension;
use Symfony\Bridge\Twig\Extension\RoutingExtension;
use Symfony\Bridge\Twig\Extension\SecurityExtension;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Bridge\Twig\Form\TwigRenderer;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;

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
            /** @var Kernel $kernel */
            $kernel = $c['kernel'];
            return $kernel->getCacheDir() . '/twig_cache';
        };

        /*
         * Return every paths to search for twig templates.
         */
        $container['twig.loaderFileSystem'] = function ($c) {
            /** @var Kernel $kernel */
            $kernel = $c['kernel'];
            $vendorDir = realpath($kernel->getVendorDir());

            // le chemin vers TwigBridge pour que Twig puisse localiser
            // le fichier form_div_layout.html.twig
            $vendorTwigBridgeDir = $vendorDir . '/symfony/twig-bridge';

            return new \Twig_Loader_Filesystem([
                // Default Form extension templates
                $vendorTwigBridgeDir . '/Resources/views/Form',
                CmsController::getViewsFolder(),
            ]);
        };

        /**
         * Main twig environment.
         *
         * @param $c
         * @return \Twig_Environment
         */
        $container['twig.environment'] = function ($c) {
            $c['stopwatch']->start('initTwig');
            $twig = new \Twig_Environment($c['twig.loaderFileSystem'], [
                'debug' => $c['kernel']->isDebug(),
                'cache' => $c['twig.cacheFolder'],
            ]);
            $c['twig.formRenderer']->setEnvironment($twig);

            foreach ($c['twig.extensions'] as $extension) {
                if ($extension instanceof \Twig_Extension) {
                    $twig->addExtension($extension);
                } else {
                    throw new \RuntimeException('Try to add Twig extension which does not extends Twig_Extension.');
                }
            }

            foreach ($c['twig.filters'] as $filter) {
                if ($filter instanceof \Twig_SimpleFilter) {
                    $twig->addFilter($filter);
                } else {
                    throw new \RuntimeException('Try to add Twig filter which does not extends Twig_SimpleFilter.');
                }
            }

            $c['stopwatch']->stop('initTwig');
            return $twig;
        };

        /**
         * Twig filters.
         *
         * We separate filters from environment to be able to
         * extend them without waking up Twig.
         *
         * @param $c
         * @return ArrayCollection
         */
        $container['twig.filters'] = function ($c) {
            $filters = new ArrayCollection();
            $filters->add($c['twig.centralTruncateExtension']);

            return $filters;
        };

        /**
         * Twig extensions.
         *
         * We separate extensions from environment to be able to
         * extend them without waking up Twig.
         *
         * @param $c
         * @return ArrayCollection
         */
        $container['twig.extensions'] = function ($c) {
            /** @var Kernel $kernel */
            $kernel = $c['kernel'];
            $extensions = new ArrayCollection();
            $extensions->add(new FormExtension(new TwigRenderer(
                $c['twig.formRenderer'],
                $c['csrfTokenManager']
            )));

            $extensions->add(new ParsedownExtension());
            $extensions->add(new HttpFoundationExtension($c['requestStack']));
            $extensions->add(new SecurityExtension($c['securityAuthorizationChecker']));
            $extensions->add(new TranslationExtension($c['translator']));
            $extensions->add(new \Twig_Extensions_Extension_Intl());
            $extensions->add($c['twig.routingExtension']);
            $extensions->add(new \Twig_Extensions_Extension_Text());
            $extensions->add(new BlockRenderExtension($c));
            $extensions->add(new UrlExtension(
                $c['request'],
                $c['nodesSourcesUrlCacheProvider'],
                (boolean) $c['settingsBag']->get('force_locale')
            ));
            $extensions->add(new RoadizTranslationExtension($c['request']));

            if (null !== $c['twig.cacheExtension']) {
                $extensions->add($c['twig.cacheExtension']);
            }
            /*
             * These extension need a valid Database connection
             * with EntityManager not null.
             */
            if (true !== $kernel->isInstallMode()) {
                $extensions->add(new DocumentExtension($c['assetPackages']));
                $extensions->add(new FontExtension($c['assetPackages']));
                $extensions->add(new NodesSourcesExtension(
                    $c['securityAuthorizationChecker'],
                    $kernel->isPreview()
                ));
            }
            if (true === $kernel->isDebug()) {
                $extensions->add(new \Twig_Extension_Debug());
            }

            return $extensions;
        };

        /**
         * Twig form renderer extension.
         *
         * @return TwigRendererEngine
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

            return new RoutingExtension($c['router']);
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
