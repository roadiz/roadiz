<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Services;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\Routing\InstallRouteCollection;
use RZ\Roadiz\Core\Routing\NodeRouter;
use RZ\Roadiz\Core\Routing\RedirectionRouter;
use RZ\Roadiz\Core\Routing\RoadizRouteCollection;
use RZ\Roadiz\Core\Routing\StaticRouter;
use RZ\Roadiz\Preview\PreviewResolverInterface;
use Symfony\Cmf\Component\Routing\ChainRouter;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Security\Http\HttpUtils;

/**
 * Register routing services for dependency injection container.
 */
class RoutingServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Container $container
     * @return Container
     */
    public function register(Container $container)
    {
        $container['httpKernel'] = function (Container $c) {
            return new HttpKernel($c['dispatcher'], $c['resolver'], $c['requestStack'], $c['argumentResolver']);
        };

        $container['requestStack'] = function () {
            return new RequestStack();
        };

        $container['requestContext'] = function () {
            return new RequestContext();
        };

        $container['resolver'] = function () {
            return new ControllerResolver();
        };

        $container['argumentResolver'] = function () {
            return new ArgumentResolver();
        };

        $container['router'] = function (Container $c) {
            /** @var Kernel $kernel */
            $kernel = $c['kernel'];
            $router = new ChainRouter($c['logger']);
            $router->setContext($c['requestContext']);
            $router->add($c['staticRouter'], 2);

            if (false === $kernel->isInstallMode()) {
                $router->add($c['nodeRouter'], 1);
                // Redirection must be first to be able to redirect nodes urls
                $router->add($c['redirectionRouter'], 3);
            }

            return $router;
        };

        $container['staticRouter'] = function (Container $c) {
            /** @var Kernel $kernel */
            $kernel = $c['kernel'];
            $config = [
                'debug' => $kernel->isDebug(),
            ];
            if ($kernel->getEnvironment() === 'prod') {
                $config = array_merge($config, [
                    'cache_dir' => $kernel->getCacheDir() . '/routing',
                    'generator_cache_class' => 'StaticUrlGenerator',
                    'matcher_cache_class' => 'StaticUrlMatcher',
                ]);
            }
            return new StaticRouter(
                $c['routeCollection'],
                $config,
                $c['requestContext'],
                $c['logger']
            );
        };

        $container['nodeRouter'] = function (Container $c) {
            /** @var Kernel $kernel */
            $kernel = $c['kernel'];
            $router = new NodeRouter(
                $c['em'],
                $c['themeResolver'],
                $c['settingsBag'],
                $c['dispatcher'],
                $c[PreviewResolverInterface::class],
                [
                    'cache_dir' => $kernel->getCacheDir() . '/routing',
                    'debug' => $kernel->isDebug(),
                ],
                $c['requestContext'],
                $c['logger'],
                $c['stopwatch']
            );
            $router->setNodeSourceUrlCacheProvider($c['nodesSourcesUrlCacheProvider']);
            return $router;
        };

        $container['redirectionRouter'] = function (Container $c) {
            /** @var Kernel $kernel */
            $kernel = $c['kernel'];
            return new RedirectionRouter(
                $c['em'],
                [
                    'cache_dir' => $kernel->getCacheDir() . '/routing',
                    'debug' => $kernel->isDebug(),
                ],
                $c['requestContext'],
                $c['logger'],
                $c['stopwatch']
            );
        };

        /*
         * As we are using CMF ChainRouter, it takes responsability for
         * URL generation.
         */
        $container['urlGenerator'] = function (Container $c) {
            return $c['router'];
        };

        $container['httpUtils'] = function (Container $c) {
            return new HttpUtils($c['router'], $c['router']);
        };

        $container['routeListener'] = function (Container $c) {
            return new RouterListener(
                $c['router'],
                $c['requestStack'],
                $c['requestContext'],
                null
            );
        };

        $container['routeCollection'] = function (Container $c) {
            /** @var Kernel $kernel */
            $kernel = $c['kernel'];
            if (true === $kernel->isInstallMode()) {
                /*
                 * Get Install routes
                 */
                call_user_func([Kernel::INSTALL_CLASSNAME, 'setupDependencyInjection'], $c);
                return new InstallRouteCollection(Kernel::INSTALL_CLASSNAME);
            } else {
                /*
                 * Get App routes
                 */
                $collection = new RoadizRouteCollection(
                    $c['themeResolver'],
                    $c['settingsBag'],
                    $c[PreviewResolverInterface::class],
                    $c['stopwatch']
                );

                return $collection;
            }
        };
        return $container;
    }
}
