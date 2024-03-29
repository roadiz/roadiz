<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Services;

use Doctrine\Persistence\ManagerRegistry;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\Roadiz\CMS\Controllers\DefaultController;
use RZ\Roadiz\CMS\Controllers\RedirectionController;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\Routing\InstallRouteCollection;
use RZ\Roadiz\Core\Routing\NodeRouter;
use RZ\Roadiz\Core\Routing\NodesSourcesPathResolver;
use RZ\Roadiz\Core\Routing\NodeUrlMatcher;
use RZ\Roadiz\Core\Routing\RedirectionRouter;
use RZ\Roadiz\Core\Routing\RoadizRouteCollection;
use RZ\Roadiz\Core\Routing\StaticRouter;
use RZ\Roadiz\Preview\PreviewResolverInterface;
use Symfony\Cmf\Component\Routing\ChainRouter;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\Controller\ContainerControllerResolver;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
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
     * @return void
     */
    public function register(Container $container)
    {
        $container['httpKernel'] = function (Container $c) {
            return new HttpKernel(
                $c['dispatcher'],
                $c[ControllerResolverInterface::class],
                $c['requestStack'],
                $c['argumentResolver']
            );
        };

        /*
         * Use a proxy for cyclic dependency issue with EventDispatcher
         */
        $container['proxy.httpKernel'] = function (Container $c) {
            $factory = new \ProxyManager\Factory\LazyLoadingValueHolderFactory();
            return $factory->createProxy(
                HttpKernel::class,
                function (&$wrappedObject, $proxy, $method, $parameters, &$initializer) use ($c) {
                    $wrappedObject = $c['httpKernel']; // instantiation logic here
                    $initializer = null; // turning off further lazy initialization
                    return true;
                }
            );
        };

        /*
         * Required for HttpKernel AbstractSessionListener
         */
        $container['request_stack'] = function (Container $c) {
            return $c['requestStack'];
        };

        $container['requestStack'] = function () {
            return new RequestStack();
        };

        $container['requestContext'] = function () {
            return new RequestContext();
        };

        $container[ControllerResolverInterface::class] = function (Container $c) {
            return new ContainerControllerResolver(new \Pimple\Psr11\Container($c));
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

        $container[NodesSourcesPathResolver::class] = function (Container $c) {
            return new NodesSourcesPathResolver(
                $c[ManagerRegistry::class],
                $c[PreviewResolverInterface::class],
                $c['stopwatch']
            );
        };

        /*
         * Defines fallback controller class for nodes-sources
         * when a dedicated controller is not found inside current theme
         */
        $container['nodeDefaultControllerClass'] = DefaultController::class;

        $container[NodeUrlMatcher::class] = function (Container $c) {
            return new NodeUrlMatcher(
                $c[NodesSourcesPathResolver::class],
                $c['requestContext'],
                $c['themeResolver'],
                $c[PreviewResolverInterface::class],
                $c['stopwatch'],
                $c['logger'],
                $c['nodeDefaultControllerClass']
            );
        };

        $container['nodeRouter'] = function (Container $c) {
            /** @var Kernel $kernel */
            $kernel = $c['kernel'];
            $router = new NodeRouter(
                $c[NodeUrlMatcher::class],
                $c['themeResolver'],
                $c['settingsBag'],
                $c['proxy.dispatcher'],
                [
                    'cache_dir' => $kernel->getCacheDir() . '/routing',
                    'debug' => $kernel->isDebug(),
                ],
                $c['requestContext'],
                $c['logger']
            );
            $router->setNodeSourceUrlCacheProvider($c['nodesSourcesUrlCacheProvider']);
            return $router;
        };

        $container['redirectionRouter'] = function (Container $c) {
            /** @var Kernel $kernel */
            $kernel = $c['kernel'];
            return new RedirectionRouter(
                $c[ManagerRegistry::class],
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
         * As we are using CMF ChainRouter, it takes responsibility for
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
                return new RoadizRouteCollection(
                    $c['themeResolver'],
                    $c['settingsBag'],
                    $c[PreviewResolverInterface::class],
                    $c['config']['staticDomainName'],
                    $c['stopwatch']
                );
            }
        };

        $container[RedirectionController::class] = function (Container $c) {
            return new RedirectionController($c['router']);
        };
    }
}
