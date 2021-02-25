<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Services;

use Monolog\Logger;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Utils\Log\Handler\DoctrineHandler;
use RZ\Roadiz\Utils\Log\LoggerFactory;
use RZ\Roadiz\Utils\Log\Processor\RequestProcessor;
use RZ\Roadiz\Utils\Log\Processor\TokenStorageProcessor;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @package RZ\Roadiz\Core\Services
 */
class LoggerServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Container $container
     * @return Container
     */
    public function register(Container $container)
    {
        $container[LoggerFactory::class] = function (Container $c) {
            return new LoggerFactory(
                $c['kernel'],
                !empty($c['config']['monolog']) ? $c['config']['monolog'] : []
            );
        };

        $container['logger.doctrine'] = function (Container $c) {
            /** @var LoggerFactory $factory */
            $factory = $c[LoggerFactory::class];
            $log = $factory->createLogger('doctrine', 'doctrine');

            /*
             * Add processors
             */
            if ($log instanceof Logger) {
                /** @var RequestStack $requestStack */
                $requestStack = $c['requestStack'];
                $log->pushProcessor(new RequestProcessor($requestStack));
                $log->pushProcessor(new TokenStorageProcessor($c['securityTokenStorage']));
            }

            return $log;
        };

        $container['logger.cli'] = function (Container $c) {
            /** @var LoggerFactory $factory */
            $factory = $c[LoggerFactory::class];
            return $factory->createLogger('roadiz', 'cli');
        };

        $container['logger'] = function (Container $c) {
            /** @var LoggerFactory $factory */
            $factory = $c[LoggerFactory::class];
            $log = $factory->createLogger('roadiz', 'roadiz');

            /*
             * Only activate doctrine logger for production.
             */
            /** @var Kernel $kernel */
            $kernel = $c['kernel'];
            if (null !== $c['em'] &&
                $log instanceof Logger &&
                false === $kernel->isInstallMode() &&
                $kernel->getEnvironment() == 'prod') {
                $log->pushHandler(new DoctrineHandler(
                    $c['em'],
                    $c['securityTokenStorage'],
                    $c['requestStack'],
                    Logger::INFO
                ));
            }

            /*
             * Add processors
             */
            if ($log instanceof Logger) {
                /** @var RequestStack $requestStack */
                $requestStack = $c['requestStack'];
                $log->pushProcessor(new RequestProcessor($requestStack));
                $log->pushProcessor(new TokenStorageProcessor($c['securityTokenStorage']));
            }

            return $log;
        };

        $container['logger.security'] = function (Container $c) {
            /** @var LoggerFactory $factory */
            $factory = $c[LoggerFactory::class];
            $log = $factory->createLogger('security', 'security');

            /*
             * Only activate doctrine logger for production.
             */
            /** @var Kernel $kernel */
            $kernel = $c['kernel'];
            if (null !== $c['em'] &&
                $log instanceof Logger &&
                false === $kernel->isInstallMode() &&
                $kernel->getEnvironment() == 'prod') {
                $log->pushHandler(new DoctrineHandler(
                    $c['em'],
                    $c['securityTokenStorage'],
                    $c['requestStack'],
                    Logger::INFO
                ));
            }

            /*
             * Add processors
             */
            if ($log instanceof Logger) {
                /** @var RequestStack $requestStack */
                $requestStack = $c['requestStack'];
                $log->pushProcessor(new RequestProcessor($requestStack));
                $log->pushProcessor(new TokenStorageProcessor($c['securityTokenStorage']));
            }

            return $log;
        };

        return $container;
    }
}
