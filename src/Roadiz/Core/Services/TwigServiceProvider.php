<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Services;

use DebugBar\DataCollector\MessagesCollector;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Exception;
use PDOException;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\Roadiz\CMS\Controllers\CmsController;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Document\Renderer\RendererInterface;
use RZ\Roadiz\Translation\Twig\TranslationExtension as RoadizTranslationExtension;
use RZ\Roadiz\Translation\Twig\TranslationMenuExtension;
use RZ\Roadiz\Utils\MediaFinders\EmbedFinderFactory;
use RZ\Roadiz\Utils\TwigExtensions\BlockRenderExtension;
use RZ\Roadiz\Utils\TwigExtensions\CentralTruncateExtension;
use RZ\Roadiz\Utils\TwigExtensions\DocumentExtension;
use RZ\Roadiz\Utils\TwigExtensions\DumpExtension;
use RZ\Roadiz\Utils\TwigExtensions\FontExtension;
use RZ\Roadiz\Utils\TwigExtensions\HandlerExtension;
use RZ\Roadiz\Utils\TwigExtensions\HttpKernelExtension;
use RZ\Roadiz\Utils\TwigExtensions\NodesSourcesExtension;
use RZ\Roadiz\Utils\TwigExtensions\RoadizExtension;
use RZ\Roadiz\Utils\TwigExtensions\RoutingExtension;
use RZ\Roadiz\Utils\TwigExtensions\UrlExtension;
use Symfony\Bridge\Twig\Extension\AssetExtension;
use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Extension\HttpFoundationExtension;
use Symfony\Bridge\Twig\Extension\SecurityExtension;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Component\Form\FormRenderer;
use Symfony\Component\HttpFoundation\UrlHelper;
use Symfony\Component\HttpKernel\Fragment\FragmentHandler;
use Symfony\Component\HttpKernel\Fragment\InlineFragmentRenderer;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Extension\ProfilerExtension;
use Twig\Extra\Html\HtmlExtension;
use Twig\Extra\Intl\IntlExtension;
use Twig\Extra\String\StringExtension;
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

            $loader = new FilesystemLoader([]);
            $loader->addPath($vendorDir . '/symfony/twig-bridge/Resources/views/Form');
            $loader->addPath($vendorDir . '/symfony/twig-bridge/Resources/views/Form', 'Twig');
            $loader->addPath($vendorDir . '/roadiz/documents/src/Roadiz/Resources/views');
            $loader->addPath($vendorDir . '/roadiz/documents/src/Roadiz/Resources/views', 'Documents');
            $loader->addPath(CmsController::getViewsFolder());
            $loader->addPath(CmsController::getViewsFolder(), 'Cms');

            return $loader;
        };

        /**
         * @param Container $c
         * @return Environment Early binding Environment to be able to use it inside Twig Extensions
         */
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
         * Not to use as a Event or Dispatcher dependency, use
         * safe_environment instead.
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
                    throw new \RuntimeException('Try to add Twig extension which does not extends AbstractExtension.');
                }
            }

            foreach ($c['twig.filters'] as $filter) {
                if ($filter instanceof TwigFilter) {
                    $twig->addFilter($filter);
                } else {
                    throw new \RuntimeException('Try to add Twig filter which does not extends TwigFilter.');
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
         * @return ArrayCollection
         */
        $container['twig.filters'] = function () {
            return new ArrayCollection();
        };

        $container['twig.fragmentHandler'] = function (Container $c) {
            /** @var Kernel $kernel */
            $kernel = $c['kernel'];
            return new FragmentHandler($c['requestStack'], [
                new InlineFragmentRenderer($kernel, $c['dispatcher']),
            ], $kernel->isDebug());
        };

        $container[UrlHelper::class] = function (Container $c) {
            return new UrlHelper($c['requestStack'], $c['requestContext']);
        };

        $container[MessagesCollector::class] = function () {
            return new MessagesCollector();
        };

        $container['twig.extensions'] = function (Container $c) {
            /** @var Kernel $kernel */
            $kernel = $c['kernel'];
            $extensions = new ArrayCollection();

            $extensions->add(new FormExtension());
            $extensions->add(new StringExtension());
            $extensions->add(new CentralTruncateExtension());
            $extensions->add(new HtmlExtension());
            $extensions->add(new RoadizExtension($kernel));
            $extensions->add(new HandlerExtension($c['factory.handler']));
            $extensions->add(new HttpFoundationExtension($c[UrlHelper::class]));
            $extensions->add(new TranslationExtension($c['translator']));
            $extensions->add(new AssetExtension($c['assetPackages']));
            $extensions->add(new IntlExtension());
            $extensions->add(new RoadizTranslationExtension());
            $extensions->add(new TranslationMenuExtension($c['requestStack'], $c['translation.viewer']));
            $extensions->add(new SecurityExtension($c['securityAuthorizationChecker']));
            $extensions->add($c['twig.routingExtension']);
            $extensions->add(new BlockRenderExtension($c['twig.fragmentHandler']));
            $extensions->add(new HttpKernelExtension($c['twig.fragmentHandler']));
            $extensions->add(new DumpExtension(
                $c[MessagesCollector::class],
                new VarCloner()
            ));
            $extensions->add(new UrlExtension(
                $c['document.url_generator'],
                $c['nodesSourcesUrlCacheProvider']
            ));
            /*
             * These extension need a valid Database connection
             * with EntityManager not null.
             */
            try {
                if ($kernel->isDebug()) {
                    $extensions->add(new ProfilerExtension($c['twig.profile']));
                }
                if (!$kernel->isInstallMode()) {
                    $extensions->add(new DocumentExtension(
                        $c[RendererInterface::class],
                        $c[EmbedFinderFactory::class],
                        $c['assetPackages'],
                    ));
                    $extensions->add(new FontExtension($c['assetPackages']));
                    $extensions->add(new NodesSourcesExtension(
                        $c['securityAuthorizationChecker'],
                        $c['factory.handler'],
                        $c['nodeSourceApi'],
                        $c['nodeTypesBag']
                    ));
                }
            } catch (Exception $e) {
            } catch (PDOException $e) {
                // Trying to use translator without DB
                // in CI or CLI environments
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

        return $container;
    }
}
