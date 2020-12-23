<?php
declare(strict_types=1);

namespace RZ\Roadiz\Preview;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Preview\EventSubscriber\PreviewModeSubscriber;
use RZ\Roadiz\Preview\EventSubscriber\PreviewBarSubscriber;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class PreviewServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritDoc
     */
    public function register(Container $container)
    {
        $container[PreviewResolverInterface::class] = function (Container $c) {
            return new KernelPreviewRevolver($c['kernel'], $c['requestStack']);
        };

        $container->extend('dispatcher', function (EventDispatcherInterface $dispatcher, Container $c) {
            /** @var Kernel $kernel */
            $kernel = $c['kernel'];
            if (!$kernel->isInstallMode()) {
                $dispatcher->addSubscriber(new PreviewModeSubscriber($c[PreviewResolverInterface::class], $c));
                $dispatcher->addSubscriber(new PreviewBarSubscriber($c[PreviewResolverInterface::class]));
            }
            return $dispatcher;
        });
    }
}
