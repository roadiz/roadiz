<?php
declare(strict_types=1);

namespace RZ\Roadiz\Preview;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\Roadiz\Preview\EventSubscriber\PreviewModeSubscriber;
use RZ\Roadiz\Preview\EventSubscriber\PreviewBarSubscriber;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class PreviewServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritDoc
     */
    public function register(Container $pimple)
    {
        $pimple[PreviewResolverInterface::class] = function (Container $c) {
            return new KernelPreviewRevolver($c['kernel'], $c['requestStack']);
        };

        $pimple->extend('dispatcher', function (EventDispatcherInterface $dispatcher, Container $c) {
            $kernel = $c['kernel'];
            if ($kernel->getEnvironment() !== 'install') {
                $dispatcher->addSubscriber(new PreviewModeSubscriber(
                    $c[PreviewResolverInterface::class],
                    $c['securityTokenStorage'],
                    $c[AuthorizationCheckerInterface::class]
                ));
                $dispatcher->addSubscriber(new PreviewBarSubscriber($c[PreviewResolverInterface::class]));
            }
            return $dispatcher;
        });
    }
}
