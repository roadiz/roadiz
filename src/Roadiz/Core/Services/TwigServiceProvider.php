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
use RZ\Roadiz\Utils\TwigExtensions\DumpExtension;
use RZ\Roadiz\Utils\TwigExtensions\FontExtension;
use RZ\Roadiz\Utils\TwigExtensions\HandlerExtension;
use RZ\Roadiz\Utils\TwigExtensions\HttpKernelExtension;
use RZ\Roadiz\Utils\TwigExtensions\NodesSourcesExtension;
use RZ\Roadiz\Utils\TwigExtensions\RoadizExtension;
use RZ\Roadiz\Utils\TwigExtensions\TranslationExtension as RoadizTranslationExtension;
use RZ\Roadiz\Utils\TwigExtensions\UrlExtension;
use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Extension\HttpFoundationExtension;
use Symfony\Bridge\Twig\Extension\RoutingExtension;
use Symfony\Bridge\Twig\Extension\SecurityExtension;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Component\Form\FormRenderer;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\HttpKernel\Fragment\FragmentHandler;
use Symfony\Component\HttpKernel\Fragment\InlineFragmentRenderer;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Extension\ProfilerExtension;
use Twig\Extensions\ArrayExtension;
use Twig\Extensions\DateExtension;
use Twig\Extensions\IntlExtension;
use Twig\Extensions\TextExtension;
use Twig\Loader\FilesystemLoader;
use Twig\Profiler\Profile;
use Twig\RuntimeLoader\FactoryRuntimeLoader;
use Twig\TwigFilter;

/**
 * Register Twig services for dependency injection container.
 */
class TwigServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Container $container
     * @return Container
     */
    public function register(Container $container)
    {
        $container['twig.cacheFolder'] = function (Container $c) {
            /** @var Kernel $kernel */
            $kernel = $c['kernel'];
            return $kernel->getCacheDir() . '/twig_cache';
        };

        /*
         * Return every paths to search for twig templates.
         */
        $container['twig.loaderFileSystem'] = function (Container $c) {
            /** @var Kernel $kernel */
            $kernel = $c['kernel'];
            $vendorDir = realpath($kernel->getVendorDir());

            $loader = new FilesystemLoader([
                // Default Form extension templates
                $vendorDir . '/symfony/twig-bridge/Resources/views/Form',
                // Documents rendering templates
                $vendorDir . '/roadiz/documents/src/Roadiz/Resources/views',
                CmsController::getViewsFolder(),
            ]);

            return $loader;
        };

        $container['twig.environment_class'] = function (Container $c) {
            return new Environment($c['twig.loaderFileSystem'], [
                'debug' => $c['kernel']->isDebug(),
                'cache' => $c['twig.cacheFolder'],
            ]);
        };

        /**
         * Twig form renderer extension.
         *
         * @param Container $c
         * @return TwigRendererEngine
         */
        $container['twig.formRenderer'] = function (Container $c) {
            return new TwigRendererEngine(
                ['form_div_layout.html.twig'],
                $c['twig.environment_class']
            );
        };

        /**
         * Main twig environment.
         *
         * @param Container $c
         * @return Environment
         */
        $container['twig.environment'] = function (Container $c) {
            $c['stopwatch']->start('initTwig');
            /** @var Environment $twig */
            $twig = $c['twig.environment_class'];

            foreach ($c['twig.extensions'] as $extension) {
                if ($extension instanceof AbstractExtension) {
                    $twig->addExtension($extension);
                } else {
                    throw new \RuntimeException('Try to add Twig extension which does not extends Twig_Extension.');
                }
            }

            foreach ($c['twig.filters'] as $filter) {
                if ($filter instanceof TwigFilter) {
                    $twig->addFilter($filter);
                } else {
                    throw new \RuntimeException('Try to add Twig filter which does not extends Twig_SimpleFilter.');
                }
            }

            /** @var TwigRendererEngine $formEngine */
            $formEngine = $c['twig.formRenderer'];
            /** @var CsrfTokenManager $csrfManager */
            $csrfManager = $c['csrfTokenManager'];

            $twig->addRuntimeLoader(new FactoryRuntimeLoader([
                FormRenderer::class => function () use ($formEngine, $csrfManager) {
                    return new FormRenderer($formEngine, $csrfManager);
                },
            ]));

            $c['stopwatch']->stop('initTwig');
            return $twig;
        };

        /**
         * Twig filters.
         *
         * We separate filters from environment to be able to
         * extend them without waking up Twig.
         *
         * @param Container $c
         * @return ArrayCollection
         */
        $container['twig.filters'] = function (Container $c) {
            $filters = new ArrayCollection();
            $filters->add($c['twig.centralTruncateExtension']);

            return $filters;
        };

        $container['twig.fragmentHandler'] = function (Container $c) {
            /** @var Kernel $kernel */
            $kernel = $c['kernel'];
            return new FragmentHandler($c['requestStack'], [
                new InlineFragmentRenderer($kernel, $c['dispatcher']),
            ], $kernel->isDebug());
        };

        /**
         * Twig extensions.
         *
         * We separate extensions from environment to be able to
         * extend them without waking up Twig.
         *
         * @param Container $c
         * @return ArrayCollection
         */
        $container['twig.extensions'] = function (Container $c) {
            /** @var Kernel $kernel */
            $kernel = $c['kernel'];
            $extensions = new ArrayCollection();

            $extensions->add(new FormExtension());
            $extensions->add(new RoadizExtension($kernel));
            $extensions->add(new HandlerExtension($c['factory.handler']));
            $extensions->add(new HttpFoundationExtension($c['requestStack']));
            $extensions->add(new SecurityExtension($c['securityAuthorizationChecker']));
            $extensions->add(new TranslationExtension($c['translator']));
            $extensions->add(new IntlExtension());
            $extensions->add($c['twig.routingExtension']);
            $extensions->add(new TextExtension());
            $extensions->add(new ArrayExtension());
            $extensions->add(new DateExtension());
            $extensions->add(new BlockRenderExtension($c['twig.fragmentHandler']));
            $extensions->add(new HttpKernelExtension($c['twig.fragmentHandler']));
            $extensions->add(new UrlExtension(
                $c['requestStack'],
                $c['assetPackages'],
                $c['urlGenerator'],
                $c['nodesSourcesUrlCacheProvider'],
                (boolean) $c['settingsBag']->get('force_locale')
            ));
            $extensions->add(new RoadizTranslationExtension($c['requestStack'], $c['translation.viewer']));

            if (null !== $c['twig.cacheExtension']) {
                $extensions->add($c['twig.cacheExtension']);
            }
            /*
             * These extension need a valid Database connection
             * with EntityManager not null.
             */
            if (true !== $kernel->isInstallMode()) {
                $extensions->add(new DocumentExtension($c));
                $extensions->add(new FontExtension($c));
                $extensions->add(new NodesSourcesExtension(
                    $c['securityAuthorizationChecker'],
                    $c['factory.handler'],
                    $c['nodeSourceApi'],
                    $kernel->isPreview()
                ));

                $extensions->add(new DumpExtension($c));
                if ($kernel->isDebug()) {
                    $extensions->add(new ProfilerExtension($c['twig.profile']));
                }
            }

            return $extensions;
        };

        $container['twig.profile'] = function () {
            return new Profile();
        };

        /*
         * Twig routing extension
         */
        $container['twig.routingExtension'] = function (Container $c) {
            return new RoutingExtension($c['router']);
        };

        /*
         * Central Truncate extension
         */
        $container['twig.centralTruncateExtension'] = function () {
            return new TwigFilter(
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
        $container['twig.cacheExtension'] = function (Container $c) {
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
