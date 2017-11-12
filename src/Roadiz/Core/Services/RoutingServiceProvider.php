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
 * @file RoutingServiceProvider.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Services;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\Roadiz\Core\Events\TimedRouteListener;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\Routing\InstallRouteCollection;
use RZ\Roadiz\Core\Routing\NodeRouter;
use RZ\Roadiz\Core\Routing\RedirectionRouter;
use RZ\Roadiz\Core\Routing\RoadizRouteCollection;
use RZ\Roadiz\Core\Routing\StaticRouter;
use Symfony\Cmf\Component\Routing\ChainRouter;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
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
        $container['httpKernel'] = function ($c) {
            return new HttpKernel($c['dispatcher'], $c['resolver'], $c['requestStack']);
        };

        $container['requestStack'] = function () {
            $stack = new RequestStack();
            return $stack;
        };

        $container['requestContext'] = function ($c) {
            $requestContext = new RequestContext();
            $requestContext->fromRequest($c['request']);
            return $requestContext;
        };

        $container['resolver'] = function () {
            return new ControllerResolver();
        };

        $container['router'] = function ($c) {
            /** @var Kernel $kernel */
            $kernel = $c['kernel'];
            $router = new ChainRouter($c['logger']);
            $router->setContext($c['requestContext']);
            $router->add($c['staticRouter'], 2);

            if (false === $kernel->isInstallMode()) {
                $router->add($c['nodeRouter'], 1);
                $router->add($c['redirectionRouter'], 0);
            }

            return $router;
        };
        $container['staticRouter'] = function ($c) {
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
        $container['nodeRouter'] = function ($c) {
            /** @var Kernel $kernel */
            $kernel = $c['kernel'];
            $router = new NodeRouter(
                $c['em'],
                $c['themeResolver'],
                $c['settingsBag'],
                [
                    'cache_dir' => $kernel->getCacheDir() . '/routing',
                    'debug' => $kernel->isDebug(),
                ],
                $c['requestContext'],
                $c['logger'],
                $c['stopwatch'],
                $kernel->isPreview()
            );
            $router->setNodeSourceUrlCacheProvider($c['nodesSourcesUrlCacheProvider']);
            return $router;
        };

        $container['redirectionRouter'] = function ($c) {
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
         * As we are using CMF ChainRouter, it take responsability for
         * URL generation.
         */
        $container['urlGenerator'] = function ($c) {
            return $c['router'];
        };

        $container['httpUtils'] = function ($c) {
            return new HttpUtils($c['router'], $c['router']);
        };

        $container['routeListener'] = function ($c) {
            return new TimedRouteListener(
                $c['router'],
                $c['requestContext'],
                null,
                $c['requestStack'],
                $c['stopwatch']
            );
        };

        $container['routeCollection'] = function ($c) {
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
                    $c['stopwatch'],
                    $kernel->isPreview()
                );

                return $collection;
            }
        };
        return $container;
    }
}
