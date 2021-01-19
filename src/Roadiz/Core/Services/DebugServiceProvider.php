<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Services;

use Doctrine\DBAL\Logging\DebugStack;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\Roadiz\Utils\DebugBar\RoadizDebugBar;
use Symfony\Component\EventDispatcher\Debug\TraceableEventDispatcher;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class DebugServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Container $container
     */
    public function register(Container $container)
    {
        $container->extend('dispatcher', function (EventDispatcherInterface $dispatcher, Container $c) {
            return new TraceableEventDispatcher($dispatcher, $c['stopwatch']);
        });

        $container['doctrine.debugstack'] = function () {
            return new DebugStack();
        };

        $container['debugbar'] = function (Container $c) {
            return new RoadizDebugBar($c);
        };

        $container['debugbar.renderer'] = function (Container $c) {
            return $c['debugbar']->getJavascriptRenderer('/themes/Debug/static');
        };
    }
}
