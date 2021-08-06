<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Services;

use Doctrine\Persistence\ManagerRegistry;
use Pimple\Container;
use RZ\Roadiz\Core\Events\ControllerMatchedSubscriber;
use RZ\Roadiz\Core\Events\DebugBarSubscriber;
use RZ\Roadiz\Core\Events\ExceptionSubscriber;
use RZ\Roadiz\Core\Events\FontLifeCycleSubscriber;
use RZ\Roadiz\Core\Events\LocaleSubscriber;
use RZ\Roadiz\Core\Events\LoggableUsernameSubscriber;
use RZ\Roadiz\Core\Events\MaintenanceModeSubscriber;
use RZ\Roadiz\Core\Events\NodeSourcePathSubscriber;
use RZ\Roadiz\Core\Events\RoleSubscriber;
use RZ\Roadiz\Core\Events\SignatureListener;
use RZ\Roadiz\Core\Events\ThemesSubscriber;
use RZ\Roadiz\Core\Events\UpdateFontSubscriber;
use RZ\Roadiz\Core\Events\UserLocaleSubscriber;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\Routing\NodesSourcesPathAggregator;
use RZ\Roadiz\Utils\Clearer\EventListener\AnnotationsCacheEventSubscriber;
use RZ\Roadiz\Utils\Clearer\EventListener\AppCacheEventSubscriber;
use RZ\Roadiz\Utils\Clearer\EventListener\AssetsCacheEventSubscriber;
use RZ\Roadiz\Utils\Clearer\EventListener\CloudflareCacheEventSubscriber;
use RZ\Roadiz\Utils\Clearer\EventListener\ConfigurationCacheEventSubscriber;
use RZ\Roadiz\Utils\Clearer\EventListener\DoctrineCacheEventSubscriber;
use RZ\Roadiz\Utils\Clearer\EventListener\MetadataCacheEventSubscriber;
use RZ\Roadiz\Utils\Clearer\EventListener\NodesSourcesUrlsCacheEventSubscriber;
use RZ\Roadiz\Utils\Clearer\EventListener\OPCacheEventSubscriber;
use RZ\Roadiz\Utils\Clearer\EventListener\ReverseProxyCacheEventSubscriber;
use RZ\Roadiz\Utils\Clearer\EventListener\RoutingCacheEventSubscriber;
use RZ\Roadiz\Utils\Clearer\EventListener\TemplatesCacheEventSubscriber;
use RZ\Roadiz\Utils\Clearer\EventListener\TranslationsCacheEventSubscriber;
use RZ\Roadiz\Utils\Security\Firewall;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpKernel\EventListener\ResponseListener;
use Symfony\Component\HttpKernel\EventListener\SessionListener;
use Symfony\Component\Messenger\MessageBusInterface;

class EventDispatcherServiceProvider implements \Pimple\ServiceProviderInterface
{
    /**
     * @inheritDoc
     */
    public function register(Container $container)
    {
        /*
         * Create a proxy for registering services which will be
         * required for Firewall which will be injected in real dispatcher.
         */
        $container['proxy.dispatcher'] = function (Container $c) {
            $factory = new \ProxyManager\Factory\LazyLoadingValueHolderFactory();
            return $factory->createProxy(
                EventDispatcher::class,
                function (&$wrappedObject, $proxy, $method, $parameters, &$initializer) use ($c) {
                    $initializer = null; // turning off further lazy initialization
                    $wrappedObject = $c['dispatcher']; // instantiation logic here
                    return true;
                }
            );
        };

        $container['dispatcher'] = function (Container $c) {
            /** @var Kernel $kernel */
            $kernel = $c['kernel'];
            $dispatcher = new EventDispatcher();
            /*
             * Firewall service is private
             */
            $dispatcher->addSubscriber(new Firewall(
                $c['firewallMap'],
                $dispatcher
            ));
            $dispatcher->addSubscriber($c['routeListener']);
            $dispatcher->addSubscriber(new SessionListener(new \Pimple\Psr11\Container($c)));
            $dispatcher->addSubscriber(new AppCacheEventSubscriber());
            $dispatcher->addSubscriber(new AssetsCacheEventSubscriber());
            $dispatcher->addSubscriber(new ConfigurationCacheEventSubscriber());
            $dispatcher->addSubscriber(new AnnotationsCacheEventSubscriber());
            $dispatcher->addSubscriber(new MetadataCacheEventSubscriber());
            $dispatcher->addSubscriber(new DoctrineCacheEventSubscriber());
            $dispatcher->addSubscriber(new NodesSourcesUrlsCacheEventSubscriber());
            $dispatcher->addSubscriber(new OPCacheEventSubscriber());
            $dispatcher->addSubscriber(new RoutingCacheEventSubscriber());
            $dispatcher->addSubscriber(new TemplatesCacheEventSubscriber());
            $dispatcher->addSubscriber(new TranslationsCacheEventSubscriber());
            $dispatcher->addSubscriber(new ReverseProxyCacheEventSubscriber(
                $c,
                $c[MessageBusInterface::class],
                $c['logger.cache']
            ));
            $dispatcher->addSubscriber(new CloudflareCacheEventSubscriber(
                $c,
                $c[MessageBusInterface::class],
                $c['logger.cache']
            ));
            $dispatcher->addSubscriber(new ResponseListener($kernel->getCharset()));
            $dispatcher->addSubscriber(new MaintenanceModeSubscriber($c));
            $dispatcher->addSubscriber(new LoggableUsernameSubscriber($c));
            $dispatcher->addSubscriber(new UpdateFontSubscriber($c[FontLifeCycleSubscriber::class]));
            $dispatcher->addSubscriber(new SignatureListener(
                $c['settingsBag'],
                $kernel::$cmsVersion,
                $kernel->isDebug()
            ));

            $dispatcher->addSubscriber(new ExceptionSubscriber(
                $c,
                $c['themeResolver'],
                $c['logger'],
                $kernel->isDebug()
            ));
            $dispatcher->addSubscriber(new ThemesSubscriber($kernel, $c['stopwatch']));
            $dispatcher->addSubscriber(new ControllerMatchedSubscriber($kernel, $c['stopwatch']));

            if (!$kernel->isInstallMode()) {
                $dispatcher->addSubscriber(new LocaleSubscriber($kernel));
                $dispatcher->addSubscriber(new UserLocaleSubscriber($c));
                $dispatcher->addSubscriber(new NodeSourcePathSubscriber($c[NodesSourcesPathAggregator::class]));
                $dispatcher->addSubscriber(new RoleSubscriber(
                    $c[ManagerRegistry::class],
                    $c['rolesBag']
                ));
            }
            /*
             * If debug, alter HTML responses to append Debug panel to view
             */
            if (!$kernel->isInstallMode() && $kernel->isDebug()) {
                $dispatcher->addSubscriber(new DebugBarSubscriber($c));
            }

            return $dispatcher;
        };
    }
}
